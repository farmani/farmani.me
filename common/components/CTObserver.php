<?php
/**
 * CTObserver class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTObserver extends CComponent
{
    /**
     * Attach event handlers here
     */
    public function init()
    {
        Restaurants::model()->attachEventHandler('onAfterSave', array($this, 'changeRestaurants'));
        Restaurants::model()->attachEventHandler('onAfterDelete', array($this, 'changeRestaurants'));
        PictureMenus::model()->attachEventHandler('onAfterSave', array($this, 'changePictureMenus'));
        PictureMenus::model()->attachEventHandler('onAfterDelete', array($this, 'changePictureMenus'));
        Menus::model()->attachEventHandler('onAfterSave', array($this, 'changeMenus'));
        Menus::model()->attachEventHandler('onAfterDelete', array($this, 'changeMenus'));
        Sections::model()->attachEventHandler('onAfterSave', array($this, 'changeSections'));
        Sections::model()->attachEventHandler('onAfterDelete', array($this, 'changeSections'));
        SectionTypes::model()->attachEventHandler('onAfterSave', array($this, 'changeSectionTypes'));
        SectionTypes::model()->attachEventHandler('onAfterDelete', array($this, 'changeSectionTypes'));
        Items::model()->attachEventHandler('onAfterSave', array($this, 'changeItems'));
        Items::model()->attachEventHandler('onAfterDelete', array($this, 'changeItems'));
        ItemTypes::model()->attachEventHandler('onAfterSave', array($this, 'changeItemTypes'));
        ItemTypes::model()->attachEventHandler('onAfterDelete', array($this, 'changeItemTypes'));
        Options::model()->attachEventHandler('onAfterSave', array($this, 'changeOptions'));
        Options::model()->attachEventHandler('onAfterDelete', array($this, 'changeOptions'));
        Ingredients::model()->attachEventHandler('onAfterSave', array($this, 'changeIngredients'));
        Ingredients::model()->attachEventHandler('onAfterDelete', array($this, 'changeIngredients'));
        IngredientsAssignedToItems::model()->attachEventHandler('onAfterDelete', array($this, 'changeIngredientsAssignedToItems'));
        IngredientsAssignedToItems::model()->attachEventHandler('onAfterDelete', array($this, 'changeIngredientsAssignedToItems'));
        IngredientUnits::model()->attachEventHandler('onAfterSave', array($this, 'changeIngredientUnits'));
        IngredientUnits::model()->attachEventHandler('onAfterDelete', array($this, 'changeIngredientUnits'));
        Images::model()->attachEventHandler('onAfterSave', array($this, 'changeImages'));
        Images::model()->attachEventHandler('onAfterDelete', array($this, 'changeImages'));
    }

    /**
     * changeRestaurants event handler
     * @param object $event
     */
    public function changeRestaurants($event)
    {
        $restaurant = $event->sender;
        $this->_deleteRestaurantsCache($restaurant);
    }

    /**
     * changePictureMenus event handler
     * @param object $event
     */
    public function changePictureMenus($event)
    {
        $menu = $event->sender;
        $this->_deletePictureMenusCache($menu);
    }

    /**
     * changeMenus event handler
     * @param object $event
     */
    public function changeMenus($event)
    {
        $menu = $event->sender;
        $this->_deleteMenusCache($menu);
    }

    /**
     * changeSections event handler
     * @param object $event
     */
    public function changeSections($event)
    {
        $section = $event->sender;
        $this->_deleteSectionsCache($section);
    }

    /**
     * changeSectionTypes event handler
     * @param object $event
     */
    public function changeSectionTypes($event)
    {
        $sectionTypes = $event->sender;
        if (!$sectionTypes->isNewRecord)
            foreach($sectionTypes->sections as $section)
                $this->_deleteSectionsCache($section);
    }

    /**
     * changeItems event handler
     * @param object $event
     */
    public function changeItems($event)
    {
        $item = $event->sender;
        $this->_deleteItemsCache($item);
    }

    /**
     * changeItemTypes event handler
     * @param object $event
     */
    public function changeItemTypes($event)
    {
        $itemType = $event->sender;
        foreach($itemType->items as $item)
            $this->_deleteItemsCache($item);
    }

    /**
     * changeOptions event handler
     * @param object $event
     */
    public function changeOptions($event)
    {
        $option = $event->sender;
        foreach($option->items as $item)
            $this->_deleteItemsCache($item);
    }

    /**
     * changeIngredientsAssignedToItems event handler
     * @param object $event
     */
    public function changeIngredientsAssignedToItems($event)
    {
        $ingredientAssignedToItems = $event->sender;
        $this->_deleteIngredientsCache($ingredientAssignedToItems->ingredient);
    }

    /**
     * changeIngredientUnits event handler
     * @param object $event
     */
    public function changeIngredientUnits($event)
    {
        $ingredientUnits = $event->sender;
        foreach($ingredientUnits->ingredients as $ingredient)
            $this->_deleteIngredientsCache($ingredient);
    }

    /**
     * changeImages event handler
     * @param object $event
     */
    public function changeImages($event)
    {
        $image = $event->sender;
        foreach($image->items as $item)
            $this->_deleteItemsCache($item);
        foreach($image->restaurants as $restaurant)
            $this->_deleteRestaurantsCache($restaurant);
        $this->_deleteUsersCache($image->user);
    }

    /**
     * _deleteUsersCache to delete user
     *
     * @param CTBaseActiveRecord $user model name
     * @access private
     */
    protected function _deleteUsersCache($user)
    {
        if (!$user->isNewRecord){
            $key = 'c:api_v1/user:view:' . $user->id;
            $this->_deleteCache($key);
            $key = 'm:User:getDataById:' . $user->id;
            $this->_deleteCache($key);
        }
    }

    /**
     * _deleteRestaurantsCache to delete restaurant with it's owners users
     *
     * @param CTBaseActiveRecord $restaurant model name
     * @access private
     */
    protected function _deleteRestaurantsCache($restaurant)
    {
        if (!$restaurant->isNewRecord){
            $key = 'c:api_v1/restaurant:view:' . $restaurant->id;
            $this->_deleteCache($key);
            $key = 'm:Restaurants:getDataById:' . $restaurant->id;
            $this->_deleteCache($key);
        }
        $this->_deleteUsersCache($restaurant->users);
    }

    /**
     * _deleteMenusCache to delete menu with it's owners restaurants
     *
     * @param CTBaseActiveRecord $menu model name
     * @access private
     */
    protected function _deletePictureMenusCache($menu)
    {
        if (!$menu->isNewRecord){
            $key = 'c:api_v1/pictureMenus:view:' . $menu->id;
            $this->_deleteCache($key);
            $key = 'm:PictureMenus:getDataById:' . $menu->id;
            $this->_deleteCache($key);
        }
        $this->_deleteRestaurantsCache($menu->restaurant);
    }

    /**
     * _deleteMenusCache to delete menu with it's owners restaurants
     *
     * @param CTBaseActiveRecord $menu model name
     * @access private
     */
    protected function _deleteMenusCache($menu)
    {
        if (!$menu->isNewRecord){
            $key = 'c:api_v1/menus:view:' . $menu->id;
            $this->_deleteCache($key);
            $key = 'm:Menus:getDataById:' . $menu->id;
            $this->_deleteCache($key);
        }
        $this->_deleteRestaurantsCache($menu->restaurant);
    }

    /**
     * _deleteSectionsCache to delete section with it's owners menus
     *
     * @param CTBaseActiveRecord $section model name
     * @access private
     */
    protected function _deleteSectionsCache($section)
    {
        if (!$section->isNewRecord){
            $key = 'c:api_v1/sections:view:' . $section->id;
            $this->_deleteCache($key);
            $key = 'm:Sections:getDataById:' . $section->id;
            $this->_deleteCache($key);
        }
        foreach($section->menus as $menu)
            $this->_deleteMenusCache($menu);
    }

    /**
     * _deleteItemsCache to delete item with it's owners section
     *
     * @param CTBaseActiveRecord $item model name
     * @access private
     */
    protected function _deleteItemsCache($item)
    {
        if (!$item->isNewRecord){
            $key = 'c:api_v1/items:view:' . $item->id;
            $this->_deleteCache($key);
            $key = 'm:Items:getDataById:' . $item->id;
            $this->_deleteCache($key);
        }
        $this->_deleteSectionsCache($item->section);
    }


    /**
     * _deleteItemsCache to delete ingredient with it's owners items
     *
     * @param CTBaseActiveRecord $ingredient model name
     * @access private
     */
    protected function _deleteIngredientsCache($ingredient)
    {
        if (!$ingredient->isNewRecord){
            $key = 'c:api_v1/ingredients:view:' . $ingredient->id;
            $this->_deleteCache($key);
            $key = 'm:Ingredients:getDataById:' . $ingredient->id;
            $this->_deleteCache($key);
        }
        foreach($ingredient->items as $item)
            $this->_deleteItemsCache($item);
    }

    /**
     * _deleteCache to invalidate cache with a unique key
     *
     * @param string $key cache key name
     * @access private
     */
    protected function _deleteCache($key)
    {
        if(!empty($key))
            Yii::app()->cache->delete($key);
    }
}