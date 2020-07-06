<?php 
	namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Companydetail;

	
	class SitemapController extends Controller
	{	
		public $successStatus = 200;
	 	public function __construct(Request $request) {
    	}
    	
    	//Get all businesses based on longitude and latitude NewCode
    	public function bussiness(Request $request) {

    		$Companydata = Companydetail::select('slug')
					->where('status','!=','deleted')
					->limit(20)
					->get();
			if($Companydata){
				$sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
				$sitemap .='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
				$modDate =   date('Y-m-d H:i:s');
				foreach ($Companydata as $cmp => $value) {
					$sitemap .='
					<url>
						<loc>https://www.marinecentral.com/biz/'.$value->slug.'</loc>
						<lastmod>'.$modDate.'</lastmod>
						<changefreq>weekly</changefreq>
						<priority>0.8</priority>
					</url>';
				}
				$sitemap = $sitemap.'
</urlset>';
				$sitemap=trim($sitemap);
				$myFile ='bizsitemap.xml';
				if (!file_exists($myFile)) {
				  $fh = fopen($myFile, 'w');
				  fwrite($fh, $sitemap."\n");
					fclose($fh);
				}
				$dom = new \DOMDocument;
				$dom->preserveWhiteSpace = TRUE;
				$dom->loadXML($sitemap);
				// Save XML as a file
				$dom->save($myFile);
			}
    	}
	}


?>
