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

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class BackButton
 * @package Zanna\Gls\Block\Adminhtml
 */
class BackButton extends GenericButton implements ButtonProviderInterface
{

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('sales/*/');
    }
}
