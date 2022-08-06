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

namespace Zanna\Gls\Block\Adminhtml;

use Magento\Framework\View\Asset\Repository;

/**
 * Class Image
 * @package Zanna\Gls\Block\Adminhtml
 */
class Image extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */

    private $assetRepo;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Repository $assetRepo
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Repository $assetRepo,
        array $data = []
    ) {
        $this->assetRepo = $assetRepo;
        parent::__construct($context, $data);
    }

    /**
     * @param $image
     * @return string
     */
    public function getImageUrl($image)
    {
        return $this->assetRepo->getUrl($image);
    }
}
