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

namespace Zanna\Gls\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;

/**
 * Class Export
 * @package Zanna\Gls\Controller\Adminhtml\Order
 */
class Export  extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction implements HttpPostActionInterface
{
    /**
     *
     */
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * @var string
     */
    protected $redirectUrl = '*/*/';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var ShipmentDocumentFactory
     */
    private $shipmentDocumentFactory;

    /**
     * @var \Fooman\PrintOrderPdf\Model\Pdf\OrderFactory
     */
    protected $orderPdfFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * Pdforders constructor.
     *
     * @param \Magento\Backend\App\Action\Context                        $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Ui\Component\MassAction\Filter                    $filter
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory           $fileFactory
     * @param \Fooman\PrintOrderPdf\Model\Pdf\OrderFactory               $orderPdfFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                $date
     * @param ShipmentDocumentFactory $shipmentDocumentFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        ShipmentDocumentFactory $shipmentDocumentFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        parent::__construct($context, $filter);
        $this->_transactionFactory = $transactionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->shipmentDocumentFactory = $shipmentDocumentFactory;
        $this->dateTime = $date;
    }

    /**
     * Set status to collection items
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countHoldOrder = 0;
        try {
            $data = array();
            /** @var \Magento\Sales\Model\Order $order */
            foreach ($collection as $order) {
                if(!$order->canShip()) {
                    continue;
                }
                if(!$order->getState() == 'new' || !empty($order->getDataByKey('do_shipment'))) {
                    continue;
                }
                $firstname = $order->getShippingAddress()->getFirstname();
                $lastname = $order->getShippingAddress()->getLastname();
                $company = $order->getShippingAddress()->getCompany();
                $street = $order->getShippingAddress()->getStreetLine(1);
                $city = $order->getShippingAddress()->getCity();
                $province = $order->getShippingAddress()->getRegionCode();
                $zip = $order->getShippingAddress()->getPostcode();
                $email = $order->getShippingAddress()->getEmail();
                $telephone = $order->getShippingAddress()->getTelephone();
                $telephone =  $telephone[0] != 0 ? $telephone : "";
                $note = $order->getCustomerNote();
                $contrassegno = $order->getPayment()->getMethod() == 'cashondelivery';
                $incasso = strlen($company) > 0 ? "AB" : "CONT";

                $delivery = strlen($company) > 0 ? $company . " - " . $firstname . " " . $lastname : $firstname . " " . $lastname;
                $data[] = array($delivery, $street, $city, $zip, $province, $order->getIncrementId(), 1, $email, 1, $contrassegno ? $order->getGrandTotal() : 0, $note, $telephone, $incasso);
                $countHoldOrder++;
            }
            if ($countHoldOrder <= 0)
            {
                throw new \Exception(
                    __("No exported shipments")
                );
            }

            $fp = fopen('php://output', 'wb');
            foreach ($data as $line) {
                fputcsv($fp, $line, "\t");
            }
            fclose($fp);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($this->getComponentRefererUrl());
            return $resultRedirect;
        }
        if ($countHoldOrder) {
            $this->messageManager->addSuccessMessage(__('You have exported %1 shippings.', $countHoldOrder));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $date = $this->dateTime->gmtDate("Y-m-d-G-i");
        $resultRedirect->setHeader('Content-Type', 'text/csv');
        $resultRedirect->setHeader('Content-Disposition', 'attachment; filename="gls_export_' . $date . '.csv"');
        return $resultRedirect;
    }
}
