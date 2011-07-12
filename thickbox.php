<?php
//error_reporting(E_ALL);
/**
 * @mosthickbox.php $Format:%ci$
 * @package thickbox
 * @author $Format:%an$ $Format:%ae$
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * $version: 3.0
 * @credit: Boris Popoff (smoothbox), Christophe Beyls (slimbox), Codey Lindley for the orignal thickbox.js
 * @description: Joomla mambot to display thickbox with ajax, static or iframed content
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');


class plgContentThickbox extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onContentPrepare($context, &$row, &$params, $page = 0 )
	{
//		$app = JFactory::getApplication();
		$live_site = JUri::base(true);
		$html = "";

		if (JPluginHelper::isEnabled('content', 'thickbox'))
		{
			$html .="<script type=\"text/javascript\" src=\"" . $live_site . "/plugins/content/thickbox/includes/smoothbox.js\"></script>\n";
			$html .="<link rel=\"stylesheet\" href=\"". $live_site . "/plugins/content/thickbox/includes/smoothbox.css\" type=\"text/css\" media=\"screen\" />\n";

			// get Lightbox switch

			$int = $this->params->get( 'slimbox', 0 );

			$th_width = $this->params->get( 'thumbnail_width' );
			$th_height = $this->params->get( 'thumbnail_height' );
			$th_quality = $this->params->get( 'thumbnail_quality' );


			// add slimbox
			if ($int == 1 )
			{
				$html .="<script type=\"text/javascript\" src=\"" . $live_site . "plugins/content/thickbox/includes/slimbox.js\"></script>\n";
				$html .="<link rel=\"stylesheet\" href=\"". $live_site . "plugins/content/thickbox/includes/slimbox.css\" type=\"text/css\" media=\"screen\" />\n";
				$html .= "<style type=\"text/css\">
		.lbLoading {
		background: #fff url(".$live_site. "plugins/content/thickbox/images/loading.gif) no-repeat center;
		}
		#lbPrevLink:hover {
		background: transparent url(".$live_site. "plugins/content/thickbox/images/prevlabel.gif) no-repeat 0% 15%;
		}
		#lbNextLink:hover {
		background: transparent url(".$live_site. "plugins/content/thickbox/images/nextlabel.gif) no-repeat 100% 15%;
		}
		#lbCloseLink {
		display: block;
		float: right;
		width: 66px;
		height: 22px;
		background: transparent url(".$live_site."plugins/content/thickbox/images/closelabel.gif) no-repeat center;
		margin: 5px 0;
		}
	</style>";
			}
			$document =& JFactory::getDocument();
			$doctype = $document->getType();
			if ($doctype == "html") $document->addCustomTag( $html );

			$row->text = $this->_procBox($row->text, $int);
			return true;

		} else {
			$row->text = preg_replace( '/\{tiframe[^\}]*\}/','', $row->text );
			$row->text = preg_replace( '/{timg[^}]*}/s','', $row->text );
			$row->text = preg_replace( '/{tinline[^}]*}/s','', $row->text );
			$row->text = preg_replace( '/{\s*thickbox[^}]*}/s','', $row->text );
			return true;
		}



	}


	protected function _procBox($text, $int = null) {


		//thickbox (new, invisible)
		//-----------------------------------------------------------------

		$regex = '#{thickbox(\s*.*?)}(.*?){/thickbox}#s';
		preg_match_all( $regex, $text, $matches);
		if(count($matches[0])>0){
			for($i=0;$i<count($matches[1]);$i++){
				$prmlist = $this->_getprm($matches[1][$i]);
				$output = '';

				if(@$prmlist['linktext']=='') $prmlist['linktext']='open box';
				if(@$prmlist['width']=='') $prmlist['width']='800';
				if(@$prmlist['height']=='') $prmlist['height']='600';
				if(isset($prmlist['thumb']) && ($prmlist['thumb'] != '') ) $link ='<img src="'.$prmlist['thumb'].'" alt="'.$prmlist['linktext'].'" border="0" />';
				else $link = $prmlist['linktext'];

				$sid = "tb".$i.time(); // generate unique ID for div
				$output = '<a href="'.JFilterOutput::ampReplace("/#TB_inline?height={height}&width={width}&inlineId=". $sid."&caption=".urlencode($prmlist['linktext'])).'" title="::'.$prmlist['linktext'].'" class="smoothbox">'.$link.'</a><div id="'.$sid.'" style="display:none;">'.$matches[2][$i].'</div>';

				$output = str_replace(array('{width}','{height}'), array($prmlist['width'],$prmlist['height']), $output);
				$text = preg_replace($regex, $output, $text, 1);

			}

		}

		//inline content (existing container)
		//-----------------------------------------------------------------

		$regex = '/{tinline\s*.*?}/i';
		preg_match_all( $regex, $text, $matches );

		if(count($matches[0])>0){
			for($i=0;$i<count($matches[0]);$i++){
				$prmlist = $this->_getprm($matches[0][$i]);
				$output = '';

				if(@$prmlist['linktext']=='') $prmlist['linktext']='open box';
				if(@$prmlist['width']=='') $prmlist['width']='300';
				if(@$prmlist['height']=='') $prmlist['height']='300';
				if(@$prmlist['inlineId']=='') break;
				if(isset($prmlist['thumb']) && ($prmlist['thumb'] != '') ) $link ='<img src="'.$prmlist['thumb'].'" alt="'.$prmlist['linktext'].'" border="0" />';
				else $link = $prmlist['linktext'];

				$output = '<a href="'. JFilterOutput::ampReplace("/#TB_inline?height={height}&width={width}&inlineId=".$prmlist['inlineId']."&caption=".urlencode($prmlist['linktext'])) . '" title="::'.$prmlist['linktext'].'" class="smoothbox">'.$link.'</a>';

				$output = str_replace(array('{width}','{height}'), array($prmlist['width'],$prmlist['height']), $output);
				$text = preg_replace($regex, $output, $text, 1);

			}

		}
		//iframe
		//-----------------------------------------------------------------
		$regex = '/{tiframe\s*.*?}/i';
		preg_match_all( $regex, $text, $matches );

		if(count($matches[0])>0){
			for($i=0;$i<count($matches[0]);$i++){
				$prmlist = $this->_getprm($matches[0][$i]);
				$output = '';
				$rel=''; $sep="?";

				if(@$prmlist['linktext']=='') $prmlist['linktext']='open box';
				if(@$prmlist['width']=='') $prmlist['width']='300';
				if(@$prmlist['height']=='') $prmlist['height']='300';
				if(@$prmlist['url']=='') break;
				if(isset($prmlist['thumb']) && ($prmlist['thumb'] != '') ) $link ='<img src="'.$prmlist['thumb'].'" alt="'.$prmlist['linktext'].'" border="0" />';
				else $link = $prmlist['linktext'];
				if(isset($prmlist['gal']) && ($prmlist['gal'] != '') ) $rel = ' rel="'.$prmlist['gal'].'" ';


				if ( strpos($prmlist['url'], '?' ) !== false ) $sep = "&";

				$output = '<a href="'.$prmlist['url'].$sep.'keepThis=true&TB_iframe=true&height={height}&width={width}&caption='.urlencode($prmlist['linktext']).'"   title="::'.$prmlist['linktext'].'" class="smoothbox"'.$rel.'>'.$link.'</a>';


				$output = JFilterOutput::ampReplace(str_replace(array('{width}','{height}'), array($prmlist['width'],$prmlist['height']), $output));
				$text = preg_replace($regex, $output, $text, 1);
			}
		}
		//image[gallery]
		//-----------------------------------------------------------------

		$regex = '/{timg\s*.*?}/i';
		preg_match_all( $regex, $text, $matches );

		if(count($matches[0])>0){
			for($i=0;$i<count($matches[0]);$i++){
				$prmlist = $this->_getprm($matches[0][$i]);
				$output = '';
				if ($int == 1) $rel = 'rel="lightbox';
				else $rel = 'class="smoothbox';
				if(@$prmlist['title']=='') $prmlist['title']='open box';
				if(@$prmlist['img']=='') break;
				if(@$prmlist['thumb']=='') break;
				if(isset($prmlist['gal']) && ($prmlist['gal'] != '') ) {
					if ($int == 1) $rel .= "[".$prmlist['gal']."]";
					else $rel .= '" rel="'.$prmlist['gal'];
				}
				$rel .= '"';
				$output = '<a href="'.$prmlist['img'].'" title="'.$prmlist['title'].'" '.$rel.'><img src="'.$prmlist['thumb'].'" border="0" alt="'.$prmlist['title'].'" /></a>';

				$text = preg_replace($regex, $output, $text, 1);
			}
		}

		//gallery from folder
		//-----------------------------------------------------------------

		$regex = "#{gallery\s*.*?}(.*?){/gallery}#s";
		$output = '';
		if (preg_match_all($regex, $text, $matches) > 0) {
			foreach ($matches[0] as $match) {
				$galdir = preg_replace("/{.+?}/", "", $match);
				unset($images);
				$imgdir = 	JPATH_SITE.'/images/'.$galdir;
				$thdir = 	JPATH_SITE.'/tmp';
				$imgpath = JURI::base(). "images/". $galdir;
				$thpath = JURI::base(). "tmp/";

				// read directory
				if ($dir = opendir($imgdir)) {
					while (($f = readdir($dir)) !== false) {
						$ext = substr(strtolower($f),-3) ;
						// check if image-extension
						if(in_array($ext,array('jpg','gif','png'))) {
							//check for thumb
							$thumb = $thdir.'/'.substr($f,0,strrpos($f,'.')).'_t'.substr($f,-4);
							if (!file_exists($thumb)){
								// generate thumb
								$this->_makeThumb($imgdir.'/'.$f,$ext, $th_width, $th_height, $th_quality);
								// $output .=	$thdir.'/'.$f . ' created.<br/>';
							}
							$images[] = $f;
						}
					}
					array_multisort($images, SORT_ASC, SORT_REGULAR);
					closedir($dir);
				}

				$prmlist = $this->_getprm($match);
				if ($int == 1) $rel = 'rel="lightbox';
				else $rel = 'class="smoothbox';
				if(@$prmlist['title']=='') $prmlist['title']='Gallery (No Title)';
				if(isset($prmlist['title']) && ($prmlist['title'] != '') ) {
					if ($int == 1) $rel .= "[".$prmlist['title']."]";
					else $rel .= '" rel="'.$prmlist['title'];
				}
				$rel .= '"';

				for($i = 0;$i < count($images);$i++) {

					$output .= '<a href="'.$imgpath.'/'.$images[$i].'" title="'.$prmlist['title'].'" '.$rel.'><img src="'.$thpath.substr($images[$i],0,strrpos($images[$i],'.')).'_t'.substr($images[$i],-4).'" border="0" alt="'.$prmlist['title'].'" /></a>'.PHP_EOL;
				}
				$text = preg_replace($regex, $output, $text, 1);
			}
		}


		return $text;
	}

	protected function _getprm($str)
	{
		$bArr = array();
		$str = str_replace("&quot;", "\"", $str);
		$str = str_replace("&#34;", "\"", $str);
		preg_match_all('/[a-zA-Z0-9]+\:=\"(.*?)\"/', $str, $arr);

		if(!count($arr)) return false;

		foreach($arr[0] as $attr){
			$pieces = explode(':=', $attr);
			$bArr[trim($pieces[0])] = substr(trim($pieces[1]),1,-1);
		}
		return $bArr;
	}


	protected function _makeThumb($_image_, $ext, $_width_min_, $_height_min_, $_quality_){

		if (!$_width_min_ || $_width_min_ == 0 )	$_width_min_ = 100;
		if (!$_height_min_ || $_height_min_ == 0  )	$_height_min_ = 75;
		if (!$_quality_ || $_quality_ == 0 )	$_quality_ = 80;
		$th_dir = 	JPATH_SITE. "/tmp";
		$name = substr($_image_, strrpos($_image_, '/'));
		$th_name = substr($name,0,strrpos($name,'.')).'_t'.substr($name,-4);

		$new_w = $_width_min_;
		$imagedata = getimagesize($_image_);

		if(!$imagedata[0])
		exit();

		$new_h = (int)($imagedata[1]*($new_w/$imagedata[0]));

		if(($_height_min_) AND ($new_h > $_height_min_)) {
			$new_h = $_height_min_;
			$new_w = (int)($imagedata[0]*($new_h/$imagedata[1]));
		}

		if ($ext == "jpg"){
			$src_img=ImageCreateFromJpeg($_image_);
			$dst_img = imagecreatetruecolor($new_w, $new_h);
			imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_w,$new_h,ImageSX($src_img),ImageSY($src_img));
			Imagejpeg($dst_img,$th_dir.'/'.$th_name, $_quality_);
		}

		elseif ($ext == "gif"){
			$dst_img=ImageCreate($new_w,$new_h);
			$src_img=ImageCreateFromGif($_image_);
			ImagePaletteCopy($dst_img,$src_img);
			ImageCopyResized($dst_img,$src_img,0,0,0,0,$new_w,$new_h,ImageSX($src_img),ImageSY($src_img));
			Imagegif($dst_img,$th_dir.'/'.$th_name, $_quality_);
		}

		elseif($ext == "png"){
			$src_img=ImageCreateFromPng($_image_);
			$dst_img = imagecreatetruecolor($new_w, $new_h);
			ImagePaletteCopy($dst_img,$src_img);
			ImageCopyResized($dst_img,$src_img,0,0,0,0,$new_w,$new_h,ImageSX($src_img),ImageSY($src_img));
			Imagepng($dst_img,$th_dir.'/'.$th_name, $_quality_);

		}

	}
}


