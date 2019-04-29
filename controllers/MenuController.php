<?php
namespace app\controllers;


class MenuController extends BaseController
{
    /**
     * @var \yii\web\user
     */
    protected $user;

    public function init()
    {
        $this->user = \Yii::$app->user;
    }

    public function actionGetMenu(){
        $menus = \Yii::$app->params['menus'];
        $authMenus = $this->getMenu($menus);
        return $authMenus;
    }

    private function getMenu($menus){
        $authMenus = [];
        foreach($menus as $menu){
            if(\Yii::$app->params['menu_check_off']
                || empty($menu['auth'])
                || $this->user->can($menu['auth'])){
                $authMenu = [
                    'name' => $menu['name'],
                    'icon' => $menu['icon'],
                    'href' => isset($menu['href']) ? $menu['href'] : ''
                ];
                if(isset($menu['submenus'])){
                    $subMenus = $this->getMenu($menu['submenus']);
                    if(empty($subMenus)){
                        continue;
                    }
                    $authMenu['submenus'] = $subMenus;
                }
                $authMenus[] = $authMenu;
            }
        }
        return $authMenus;
    }
}