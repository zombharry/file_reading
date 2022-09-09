<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FilereaderController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('filereader/index.html.twig');
    }

    #[Route('/list/{level<\d+>}',name:'list',methods:['GET'])]
    public function list( ProductRepository $productRepository,int $level = null): Response
    {
        

        if($level)
        {


            $products=$productRepository->findGreaterOrEqualLevel($level);

        }
        else{
            $products=$productRepository->findAll();
        }
        return $this->render('filereader/list.html.twig', [
            'level' => $level,
            'products' =>$products
        ]);
    }
    
    #[Route('/upload',name:'upload',methods:['POST'])]
    public function upload(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager=$doctrine->getManager();
        $content="";
        if(!($request->getMethod="POST"))
        {
            return $this->render('filereader/uploadresponse.html.twig', [
                
                'response_message' =>'Not a post method'
            ]);
            
    
        }
        if( !($request->files->get('myfile') instanceof UploadedFile)){
            return $this->render('filereader/uploadresponse.html.twig', [
                
                'response_message' =>'Not a file'
            ]);
        }
        if($request->files->get('myfile')->guessExtension()!='csv')
        {
            return $this->render('filereader/uploadresponse.html.twig', [
                
                'response_message' =>'File is not a .csv file'
            ]);
        }
        $content=trim(file_get_contents($request->files->get('myfile')->getPathname()));
            $myarray=preg_split("/\r\n|\r|\n/", $content);
            for ($i=1; $i < count($myarray); $i++) { 
                $line=explode(',',$myarray[$i]);
                $product=new Product();
                $product->setTitle($line[1]);
                $product->setLevel(intval($line[2]));
                $product->setNet(floatval($line[3]));
                $product->setCount(intval($line[4]));
                $product->setVat(intval($line[5]));
                $gross=$this->sumGrossCalculator(
                        $product->getNet(),
                        $product->getCount(),
                        $product->getVat()

                );
                $product->setSum_gross_val($gross);
                $entityManager->persist($product);

            }
        $entityManager->flush();
        
        return $this->render('filereader/uploadresponse.html.twig', [
                
            'response_message' =>'Data succesfully uploaded'
        ]);


        
    }
    protected function sumGrossCalculator($net,$count,$vat):float
    {
        $gross=$net*(1+($vat/100));

        return $count*$gross;

    }
    



}
