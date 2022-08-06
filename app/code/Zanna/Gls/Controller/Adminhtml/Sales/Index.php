<?php
/**
 * Zanna Gls Weblabeling Module
 *
 * @author                Zanna Bianca
 * @package             Zanna Gls
 * @version               1.0.0
 * @copyright           2022 Zanna Bianca
 * @license                http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Zanna\Gls\Controller\Adminhtml\Sales;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Zanna\Gls\Controller\Adminhtml\Sales
 */
class Index  extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;


    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Sales::sales_order');
        $resultPage->addBreadcrumb(__('Import GLS'), __('Import GLS'))
            ->getConfig()->getTitle()->prepend(__('Import GLS'));
        return $resultPage;
    }
}
