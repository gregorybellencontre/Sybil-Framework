<?php
namespace Demo\ErrorBundle\Controller;

use Sybil\Controller;

use Sybil\Model\ArticleModel;
use Sybil\App;
use Sybil\Translation;

class ErrorController extends Controller {
	
	public function error404Action() {
		$this->render('404');
	}
	
}