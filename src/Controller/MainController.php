<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends Controller
{
    /**
     * @Route("/", name="namespaces")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request)
    {
        $rootDir = $this->getParameter('kernel.root_dir');
        $namespacesFolder = $rootDir.'/../var/namespaces/';
        $scanDir = scandir($namespacesFolder);

        return $this->render('main/index.html.twig', [
            'folders' => $scanDir,
        ]);
    }

    /**
     * @Route("/namespace/{namespace}", name="namespace")
     * @param Request $request
     * @return Response
     */
    public function namespace(Request $request, $namespace)
    {
        $form = $this->uploadForm($namespace);
        $rootDir = $this->getParameter('kernel.root_dir');
        $namespaceFolder = $rootDir.'/../var/namespaces/'.$namespace;
        if(!is_dir($namespaceFolder)){
            mkdir($namespaceFolder);
        }
        $scanDir = scandir($namespaceFolder);

        return $this->render('main/namespace.html.twig', [
            'files' => $scanDir,
            'namespace' => $namespace,
            'uploadForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/namespace/{namespace}/upload-file", name="file_upload")
     * @param Request $request
     * @return Response
     */
    public function fileUpload(Request $request, $namespace)
    {
        $rootDir = $this->getParameter('kernel.root_dir');
        $namespaceFolder = $rootDir.'/../var/namespaces/'.$namespace;

        $form = $this->uploadForm($namespace);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $filename = rand(1,1000).'-'.$data['name'].'-'.$file->getClientOriginalName();
            $file->move($namespaceFolder, $filename);

            return $this->redirectToRoute('namespace', ['namespace' => $namespace]);
        }

        return $this->render('konferans/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/namespace/{namespace}/file/{firstFile}", name="file")
     * @param Request $request
     * @return Response
     */
    public function fileAction(Request $request, $namespace, $firstFile)
    {
        $compares = [];
        $rootDir = $this->getParameter('kernel.root_dir');
        $namespaceFolder = $rootDir.'/../var/namespaces/'.$namespace.'/';
        $firstFilePath = $namespaceFolder.$firstFile;
        $files = scandir($namespaceFolder);

        foreach ($files as $file){
            if($file == '..' || $file == '.' || $file == $firstFile){
                continue;
            }
            $firstFileContent = file_get_contents($firstFilePath);
            $secondFileContent = file_get_contents($namespaceFolder.$file);
            similar_text($firstFileContent, $secondFileContent, $similarity);
            $compares[] = [
                'file' => $file,
                'similarity' => $similarity,
            ];
        }

        return $this->render('main/file.html.twig', [
            'compares' => $compares,
            'firstFile' => $firstFile,
            'namespace' => $namespace,
        ]);
    }

    /**
     * @Route("/namespace/{namespace}/show-diff/{firstFile}/{secondFile}", name="show_diff")
     * @param Request $request
     * @return Response
     */
    public function showDiff(Request $request, $namespace, $firstFile, $secondFile)
    {
        $rootDir = $this->getParameter('kernel.root_dir');
        $namespaceFolder = $rootDir.'/../var/namespaces/'.$namespace.'/';
        $firstFileContent = file_get_contents($namespaceFolder.$firstFile);
        $secondFileContent = file_get_contents($namespaceFolder.$secondFile);

        return $this->render('main/diff.html.twig', [
            'namespace' => $namespace,
            'firstFile' => $firstFile,
            'secondFile' => $secondFile,
            'firstFileContent' => $firstFileContent,
            'secondFileContent' => $secondFileContent,
        ]);
    }

    private function uploadForm($namespace)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('file_upload', [
                'namespace' => $namespace,
            ]))
            ->add('name', null, [
                'label' => 'Uploader Name',
            ])
            ->add('file', FileType::class, [
                'label' => 'File'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Upload File',
            ])
            ->getForm()
        ;
    }
}