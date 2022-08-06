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

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;

/**
 * Class Save
 * @package Zanna\Gls\Controller\Adminhtml\Sales
 */
class Save extends \Magento\Backend\App\Action implements HttpPostActionInterface
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
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * The ShipmentFactory is used to create a new Shipment.
     *
     * @var Order\ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * The ShipmentRepository is used to load, save and delete shipments.
     *
     * @var Order\ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * The ShipmentNotifier class is used to send a notification email to the customer.
     *
     * @var ShipmentNotifier
     */
    protected $shipmentNotifier;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * Pdforders constructor.
     *
     * @param \Magento\Backend\App\Action\Context                        $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory           $fileFactory
     * @param \Fooman\PrintOrderPdf\Model\Pdf\OrderFactory               $orderPdfFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                $date
     * @param ShipmentDocumentFactory $shipmentDocumentFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        CollectionFactory $collectionFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        ShipmentDocumentFactory $shipmentDocumentFactory,
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Sales\Model\ResourceModel\Order $orderResourceModel,
        OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->shipmentDocumentFactory = $shipmentDocumentFactory;
        $this->_objectManager = $objectManager;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->formKeyValidator = $formKeyValidator;
        $this->orderResourceModel = $orderResourceModel;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Set status to collection items
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $countHoldOrder = 0;
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/');
        }
        $file = $this->getRequest()->getFiles('list');
        if (!isset($file))
        {
            $this->messageManager->addErrorMessage(__("No files uploaded"));
            return $resultRedirect->setPath('*/*/');
        }
        if ($file['type'] != 'text/plain')
        {
            $this->messageManager->addErrorMessage(__("Wrong file type"));
            return $resultRedirect->setPath('*/*/');
        }

        $fp = fopen($file['tmp_name'], 'r');

        while ( !feof($fp) )
        {
            $line = fgets($fp, 2048);
            $trackId = substr($line, 0, 9);
            $orderNumber = substr($line, 214, 11);
            /** @var \Magento\Sales\Model\Order $order **/
            $order = $this->collectionFactory->create()->addAttributeToFilter('increment_id', $orderNumber)
                ->getFirstItem();

            if(!isset($order) || !$order->canShip() || !$order->getState() == 'new' || !empty($order->getDataByKey('do_shipment'))) {
                continue;
            }

            $itemArr = [];
            $orderItems = $order->getItems();
            foreach ($orderItems as $item) {
                $itemArr[$item->getId()] = (int)$item->getQtyOrdered();
            }
            /** @var Shipment $shipment */
            $shipment = $this->shipmentFactory->create($order, $itemArr);

            /** @var ShipmentTrackInterface $track */
            $track = $this->_objectManager->create(ShipmentTrackInterface::class);
            $track->setNumber($trackId)
                ->setTitle('GLS')
                ->setCarrierCode('GLS');

            $shipment->addTrack($track);
            $shipment->register();
            $order->setIsInProcess(true);

            // save the newly created shipment
            $this->shipmentRepository->save($shipment);
            $this->orderRepository->save($order);

            // send shipping confirmation e-mail to customer
            $this->shipmentNotifier->notify($shipment);
            $countHoldOrder++;
        }
        fclose($fp);
        $this->messageManager->addSuccessMessage(__('You have imported %1 shippings.', $countHoldOrder));
        return $resultRedirect->setPath('*/*/');
    }
}
