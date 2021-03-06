<?php

/*
 * @copyright  Copyright (c) 2012 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Listing_Product_Category extends Mage_Adminhtml_Block_Widget_View_Container
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('amazonListingProductCategory');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_amazon_listing';
        //------------------------------

        // Set header text
        //------------------------------
        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $componentName = ' ' . Ess_M2ePro_Helper_Component_Amazon::TITLE;
        } else {
            $componentName = '';
        }

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');
        $headerText = Mage::helper('M2ePro')->__("Add Products To%component_name% Listing \"%title%\" From Categories");
        $this->_headerText = str_replace(array('%title%','%component_name%'),
                                         array($this->escapeHtml($listingData['title']), $componentName),
                                         $headerText);
        //------------------------------

        // Set buttons actions
        //------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        if (!is_null($this->getRequest()->getParam('back'))) {

            $this->_addButton('back', array(
                'label'     => Mage::helper('M2ePro')->__('Back'),
                'onclick'   => 'AmazonListingCategoryHandlerObj.back_click(\''
                    .Mage::helper('M2ePro')
                        ->getBackUrl('*/adminhtml_listing/index',
                        array('tab' =>
                        Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_AMAZON)).
                    '\')',
                'class'     => 'back'
            ));
        }

        $this->_addButton('reset', array(
            'label'     => Mage::helper('M2ePro')->__('Refresh'),
            'onclick'   => 'AmazonListingCategoryHandlerObj.reset_click()',
            'class'     => 'reset'
        ));

        $url = $this->getUrl(
            '*/adminhtml_amazon_listing/categoryProduct',
            array('id' => $listingData['id'],'add_products'=>'yes', 'next' => 'yes')
        );
        $this->_addButton('save_and_next', array(
            'label'     => Mage::helper('M2ePro')->__('Next'),
            'onclick'   => 'AmazonListingCategoryHandlerObj.save_click(\''.$url.'\')',
            'class'     => 'next save_and_next_button'
        ));

        //$url = $this->getUrl('*/adminhtml_amazon_listing/product',array(
        //    'id' => $listingData['id'],
        //    'back' => Mage::helper('M2ePro')->getBackUrlParam('*/adminhtml_listing/index')
        //));
        //$this->_addButton('save_and_list', array(
        //    'label'     => Mage::helper('M2ePro')->__('Save And List'),
        //    'onclick'   => 'AmazonListingCategoryHandlerObj.save_and_list_click(\''.$url.'\')',
        //    'class'     => 'save save_and_list_button'
        //));

        $url = $this->getUrl('*/adminhtml_amazon_listing/add',array('add_products'=>'yes'));
        $this->_addButton('save_and_go_to_listing_view', array(
            'label'     => Mage::helper('M2ePro')->__('Save'),
            'onclick'   => 'AmazonListingCategoryHandlerObj.save_click(\'' . $url .'\')',
            'class'     => 'save save_and_go_to_listings_view_button'
        ));
        //------------------------------
    }

    protected function _toHtml()
    {
        $treeSettings = array(
            'show_products_amount' => true,
            'hide_products_this_listing' => true
        );
        $categoryTreeBlock = $this->getLayout()
            ->createBlock('M2ePro/adminhtml_listing_category_tree','',array(
            'component'=>Ess_M2ePro_Helper_Component_Amazon::NICK,
            'tree_settings' => $treeSettings
        ));
        $helpBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_product_category_help');
        $categoryBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_product_category_edit');

        return '<div id="add_products_progress_bar"></div>'.
            '<div id="add_products_container">'.
            parent::_toHtml() . $helpBlock->toHtml() . $categoryTreeBlock->toHtml() . $categoryBlock->toHtml().
            '</div>';
    }
}