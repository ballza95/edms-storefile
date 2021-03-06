<?php
/**
 * @filesource modules/dms/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Dms\Index;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=dms
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var object
     */
    private $category;

    /**
     * แสดงรายการเอกสารส่ง.
     *
     * @param Request $request
     * @param array $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // ค่าที่ส่งมา
        $params = array(
            'module' => 'borrow-search',
            'from' => $request->request('from')->date(),
            'to' => $request->request('to')->date(),
            'search' => $request->request('search')->topic(),
        );
        foreach (Language::get('DMS_CATEGORIES') as $k => $label) {
            $params[$k] = $request->request($k)->toInt();
        }
        // หมวดหมู่
        $this->category = \Dms\Category\Model::init();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Dms\Index\Model::toDataTable($params, $login),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('dmsIndex_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'create_date DESC',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'dms_id', 'url'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                array(
                    'name' => 'from',
                    'type' => 'date',
                    'text' => '{LNG_from}',
                    'value' => $params['from'],
                ),
                array(
                    'name' => 'to',
                    'type' => 'date',
                    'text' => '{LNG_to}',
                    'value' => $params['to'],
                ),
            ),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/dms/model/index/action',
            'actionCallback' => 'dataTableActionCallback',
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'create_date' => array(
                    'text' => '{LNG_Date}',
                ),
                'document_no' => array(
                    'text' => '{LNG_Document No.}',
                ),
                'topic' => array(
                    'text' => '{LNG_Document title}',
                ),
                'file_name' => array(
                    'text' => '{LNG_File name}',
                ),
                'ext' => array(
                    'text' => '',
                ),
                'downloads' => array(
                    'text' => '',
                ),
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'ext' => array(
                    'class' => 'center',
                ),
                'downloads' => array(
                    'class' => 'center',
                ),
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'download' => array(
                    'class' => 'icon-download button purple',
                    'id' => ':dms_id_:id',
                    'text' => '{LNG_Download}',
                ),
                'detail' => array(
                    'class' => 'icon-info button orange',
                    'id' => ':dms_id_:id',
                    'text' => '{LNG_Detail}',
                ),
            ),
        ));
        foreach (Language::get('DMS_CATEGORIES') as $k => $label) {
            if ($k != 'department') {
                $table->filters[] = array(
                    'name' => $k,
                    'text' => $label,
                    'options' => array(0 => '{LNG_all items}') + $this->category->toSelect($k),
                    'value' => $params[$k],
                );
            }
            $table->headers[$k] = array(
                'text' => $label,
                'class' => 'center',
            );
            $table->cols[$k] = array(
                'class' => 'center',
            );
        }
        $table->filters['search'] = array(
            'name' => 'search',
            'type' => 'text',
            'text' => '{LNG_Search}',
            'value' => $params['search'],
        );
        // save cookie
        setcookie('dmsIndex_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        if ($item['url'] != '') {
            $item['ext'] = '';
        } else {
            $item['ext'] = '<img src="'.(is_file(ROOT_PATH.'skin/ext/'.$item['ext'].'.png') ? WEB_URL.'skin/ext/'.$item['ext'].'.png' : WEB_URL.'skin/ext/file.png').'" alt="'.$item['ext'].'">';
        }
        $item['downloads'] = '<span id="downloads_'.$item['id'].'" class="icon-valid color-'.(empty($item['downloads']) ? 'silver' : 'green').' notext"></span>';
        return $item;
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param array $item
     *
     * @return array
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        if ($btn == 'download') {
            if ($item['url'] != '') {
                $attributes['href'] = $item['url'];
                $attributes['class'] = 'button blue icon-world';
                $attributes['text'] = '{LNG_URL}';
                $attributes['target'] = '_blank';
            }
            return $attributes;
        } else {
            return $attributes;
        }
    }
}
