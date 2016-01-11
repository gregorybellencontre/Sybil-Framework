<?php
namespace Demo\IndexBundle\Controller;

use Sybil\Controller;
use Sybil\App;
use Sybil\Translation;
use Sybil\Collection;
use Demo\IndexBundle\Model\Article;
use Demo\UserBundle\Model\User;

class IndexController extends Controller {
	
	public function indexAction() {
		$this->redirect('accueil',array(12));
	
		$this->setTranslationVar('nom','Grégory');
	    echo $this->translate("hello");
		
		$this->vars->set('transvars',array('name'=>'Greg','lastname'=>'Bellencontre'));
		$this->vars->set('page_title','Index');
		$this->render('index');
	}
	
	public function accueilAction($id) {
		$article = new Article();
		$article->setTitle('Titre article');
		
		$array = array(
			'brothers' => 'John',
			'sisters' => ['Jil','Ana'],
			'parents' => [
				'father' => 'Marc',
				'mother' => 'Jade'
			]
		);
		
		$names = new Collection($array);
		
		while ($name = $names->fetch()) {
			echo $name->isEven() ? '* ' : '';
			
			if ($name->isCollection()) {
				echo $name->key() . ' (' . $name->length() . ') : ' . $name->implode(', ') . '<br>';
			}
			else {
				echo $name->key() . ' (' . $name->length() . ') : ' . $name . '<br>';
			}
		}
		
		$user = new User();
		$user->setLogin('Grégory');
		
		$tab = [
			'id' => 1254,
			'title' => 'Mon titre',
			'author' => $user
		];
		
		$article->fill($tab);
		
		$data = $article->getRecord();
		
		$article->save();
		
		$test = [
			'test' => '<html>',
			'roles' => [
				'admin' => '<body>',
				'user' => ['<truc>', '<machin>']
			]
		];
		
		$test = new Collection($test);
		
		App::debug($test->toArray());
		
		$this->vars->set('page_title','Accueil');
		$this->render('index');
	}
	
	public function moduleAction() {
		$this->render('module');
	}
	
}