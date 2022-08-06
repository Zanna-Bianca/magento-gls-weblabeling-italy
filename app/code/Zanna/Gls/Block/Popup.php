<?php

/**
 * Zanna Gls Weblabeling Module
 *
 * @author              Zanna Bianca
 * @package             Zanna Gls
 * @version             1.0.0
 * @copyright           2022 Zanna Bianca
 * @license             http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Zanna\Gls\Block;

use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Class Popup
 * @package Zanna\Gls\Block
 */
class Popup extends \Magento\Shipping\Block\Tracking\Popup
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $_curl;


    /**
     * Popup constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Registry $registry
     * @param DateTimeFormatterInterface $dateTimeFormatter
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Registry $registry,
        DateTimeFormatterInterface $dateTimeFormatter
    )
    {
        $this->_curl = $curl;
        $this->_curl->setOptions(
            [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0',
            ]
        );
        parent::__construct($context, $registry, $dateTimeFormatter);
    }


    /**
     * @param $track
     * @return string
     */
    public function getType($track)
    {
        $title = $track['title'];
        if (isset($track['number']) && $track['number'] != "") {
            return strtolower($title);
        }
        return "";
    }


    /**
     * @param $track
     * @return string
     */
    public function getHtmlTrack($track)
    {
        $title = $track['title'];
        if (isset($track['number']) && $track['number'] != "") {
            switch (strtolower($title)) {
                case "gls":
                    if ($track['number'][0] != 'M')
                        $track['number'] = 'MS' . $track['number'];
                        $this->_curl->post('https://www.gls-italy.com/it/servizi-per-destinatari/dettaglio-spedizione',
                        ["numero_spedizione" => $track['number'],
                        "tipo_codice" => "nazionale",
                        "mode" => "search"]);
                    return $this->_curl->getBody();
                    break;
            }
        }
        return "";
    }
}
