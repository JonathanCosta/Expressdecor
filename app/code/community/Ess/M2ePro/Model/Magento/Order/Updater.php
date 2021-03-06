<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Magento_Order_Updater
{
    // ->__('Cancel is not allowed for Orders which were already Canceled.')
    // ->__('Cancel is not allowed for Orders with Invoiced Items.')
    // ->__('Cancel is not allowed for Orders which were put on Hold.')
    // ->__('Cancel is not allowed for Orders which were Completed or Closed.')

    // ########################################

    /** @var $magentoOrder Mage_Sales_Model_Order */
    private $magentoOrder = NULL;

    private $needSave = false;

    // ########################################

    /**
     * Set magento order for updating
     *
     * @param Mage_Sales_Model_Order $order
     * @return Ess_M2ePro_Model_Magento_Order_Updater
     */
    public function setMagentoOrder(Mage_Sales_Model_Order $order)
    {
        $this->magentoOrder = $order;

        return $this;
    }

    // ########################################

    private function getMagentoCustomer()
    {
        if ($this->magentoOrder->getCustomerIsGuest()) {
            return null;
        }

        $customer = $this->magentoOrder->getCustomer();
        if ($customer instanceof Varien_Object && $customer->getId()) {
            return $customer;
        }

        $customer = Mage::getModel('customer/customer')->load($this->magentoOrder->getCustomerId());
        if ($customer->getId()) {
            $this->magentoOrder->setCustomer($customer);
        }

        return $customer->getId() ? $customer : null;
    }

    // ########################################

    /**
     * Update shipping address
     *
     * @param array $addressInfo
     */
    public function updateShippingAddress(array $addressInfo)
    {
        $shippingAddress = $this->magentoOrder->getShippingAddress();
        if ($shippingAddress instanceof Mage_Sales_Model_Order_Address) {
            $shippingAddress->addData($addressInfo);
            $shippingAddress->implodeStreetAddress()->save();
        } else {
            /** @var $shippingAddress Mage_Sales_Model_Order_Address */
            $shippingAddress = Mage::getModel('sales/order_address');
            $shippingAddress->setCustomerId($this->magentoOrder->getCustomerId());
            $shippingAddress->addData($addressInfo);
            $shippingAddress->implodeStreetAddress();

            // we need to set shipping address to order before address save to init parent_id field
            $this->magentoOrder->setShippingAddress($shippingAddress);
            $shippingAddress->save();
        }

        // we need to save order to update data in table sales_flat_order_grid
        // setData method will force magento model to save entity
        $this->magentoOrder->setForceUpdateGridRecords(false);
        $this->needSave = true;
    }

    // ########################################

    /**
     * Update customer email
     *
     * @param $email
     * @return null
     */
    public function updateCustomerEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        if ($this->magentoOrder->getCustomerIsGuest()) {
            if ($this->magentoOrder->getCustomerEmail() != $email) {
                $this->magentoOrder->setCustomerEmail($email);
                $this->needSave = true;
            }

            return;
        }

        $customer = $this->getMagentoCustomer();

        if (is_null($customer)) {
            return;
        }

        if ($customer->getEmail() != $email) {
            $customer->setEmail($email)->save();
        }
    }

    /**
     * Update customer address
     *
     * @param array $customerAddress
     */
    public function updateCustomerAddress(array $customerAddress)
    {
        if ($this->magentoOrder->getCustomerIsGuest()) {
            return;
        }

        $customer = $this->getMagentoCustomer();

        if (is_null($customer)) {
            return;
        }

        /** @var $customerAddress Mage_Customer_Model_Address */
        $customerAddress = Mage::getModel('customer/address')
            ->setData($customerAddress)
            ->setCustomerId($customer->getId())
            ->setIsDefaultBilling(false)
            ->setIsDefaultShipping(false);
        $customerAddress->implodeStreetAddress();
        $customerAddress->save();
    }

    // ########################################

    /**
     * Update payment data (payment method, transactions, etc)
     *
     * @param array $newPaymentData
     */
    public function updatePaymentData(array $newPaymentData)
    {
        $payment = $this->magentoOrder->getPayment();

        if ($payment instanceof Mage_Sales_Model_Order_Payment) {
            $payment->setAdditionalData(serialize($newPaymentData))->save();
        }
    }

    // ########################################

    /**
     * Add notes
     *
     * @param mixed $comments
     * @return null
     */
    public function updateComments($comments)
    {
        if (empty($comments)) {
            return;
        }

        !is_array($comments) && $comments = array($comments);

        $header = '<br /><b><u>' . Mage::helper('M2ePro')->__('M2E Pro Notes') . ':</u></b><br /><br />';
        $comments = implode('<br /><br />', $comments);

        $this->magentoOrder->addStatusHistoryComment($header . $comments);
        $this->needSave = true;
    }

    // ########################################

    /**
     * Update status
     *
     * @param $status
     * @return null
     */
    public function updateStatus($status)
    {
        if ($status == '') {
            return;
        }

        $this->magentoOrder->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status);
        $this->needSave = true;
    }

    // ########################################

    public function cancel()
    {
        $this->magentoOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
        $this->magentoOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_UNHOLD, true);

        if ($this->magentoOrder->isCanceled()) {
            //throw new Exception('Cancel is not allowed for Orders which were already Canceled.');
            return;
        }

        if ($this->magentoOrder->canUnhold()) {
            throw new Exception('Cancel is not allowed for Orders which were put on Hold.');
        }

        if ($this->magentoOrder->getState() === Mage_Sales_Model_Order::STATE_COMPLETE ||
            $this->magentoOrder->getState() === Mage_Sales_Model_Order::STATE_CLOSED) {
            throw new Exception('Cancel is not allowed for Orders which were Completed or Closed.');
        }

        $allInvoiced = true;
        foreach ($this->magentoOrder->getAllItems() as $item) {
            if ($item->getQtyToInvoice()) {
                $allInvoiced = false;
                break;
            }
        }
        if ($allInvoiced) {
            throw new Exception('Cancel is not allowed for Orders with Invoiced Items.');
        }

        $this->magentoOrder->cancel()->save();
    }

    // ########################################

    /**
     * Save magento order only once and only if it's needed
     */
    public function finishUpdate()
    {
        if ($this->needSave) {
            $this->magentoOrder->save();
        }
    }

    // ########################################
}