<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Companydetail;

class MakeBizSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MakeBizSitemap:bizsitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $Companydata = Companydetail::select('slug')
        ->where('status','!=','deleted')
        ->get();
        if($Companydata){
            $finalxml='';
            $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
            $sitemap .='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
            $modDate =   date('c',time());
            $sitemap_1 ='';
            $sitemap_2 ='';
            $sitemap_3 ='';
            $i=1;
            foreach ($Companydata as $cmp => $value) {
                if($i <= 50000){
                    $sitemap_1 .= '
    <url>
        <loc>https://www.marinecentral.com/biz/'.$value->slug.'</loc>
        <lastmod>'.$modDate.'</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
';    
                } else if($i > 50000 && $i < 100000){
                    $sitemap_2 .= '
    <url>
        <loc>https://www.marinecentral.com/biz/'.$value->slug.'</loc>
        <lastmod>'.$modDate.'</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
';    
                }else{
                    $sitemap_3 .= '
    <url>
        <loc>https://www.marinecentral.com/biz/'.$value->slug.'</loc>
        <lastmod>'.$modDate.'</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
';    
                }
                $i++;
            }
           
            if($sitemap_1 !='' ){
                 $sitemap_1 = $sitemap.$sitemap_1.'</urlset>';
                $finalxml=trim($sitemap_1);
                $myFile ='bizsitemap.xml';
                if (!file_exists($myFile)) {
                    $fh = fopen($myFile, 'w');
                    fwrite($fh, $finalxml."\n");
                    fclose($fh);
                }
                $dom = new \DOMDocument;
                $dom->preserveWhiteSpace = TRUE;
                $dom->loadXML($finalxml);
                // Save XML as a file
                $dom->save($myFile);
            }
            if($sitemap_2 !='' ){
                $sitemap_2 = $sitemap.$sitemap_2.'</urlset>';
                $finalxml=trim($sitemap_2);
                $myFile ='bizsitemap1.xml';
                if (!file_exists($myFile)) {
                    $fh = fopen($myFile, 'w');
                    fwrite($fh, $finalxml."\n");
                    fclose($fh);
                }
                $dom = new \DOMDocument;
                $dom->preserveWhiteSpace = TRUE;
                $dom->loadXML($finalxml);
                // Save XML as a file
                $dom->save($myFile);
            }
            if($sitemap_3 !='' ){
                $sitemap_3 = $sitemap.$sitemap_3.'</urlset>';
                $finalxml=trim($sitemap_3);
                $myFile ='bizsitemap2.xml';
                if (!file_exists($myFile)) {
                    $fh = fopen($myFile, 'w');
                    fwrite($fh, $finalxml."\n");
                    fclose($fh);
                }
                $dom = new \DOMDocument;
                $dom->preserveWhiteSpace = TRUE;
                $dom->loadXML($finalxml);
                // Save XML as a file
                $dom->save($myFile);
            }
        }
    }
}
