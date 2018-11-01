<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-04-10
 * Time: 14:37
 */

namespace common\widgets;


use yii\helpers\Html;
use yii\widgets\LinkPager;

class MLinkPager extends LinkPager
{
    /**
     *@var boole|string. weather show totalPage
     *You can do not set the property by using defult,so the totalPage button not show in the html.
     *if you set it as string,eg: totalPageLable => '共x页'.note that the 'x' will be replaced by $pageCount.so the 'x' must be seted.
     */
    public $totalPageLable = false;

    /**
     *@var boole if is seted true,the goPageLabel can be show in the html.
     *
     */
    public $goPageLabel = false;

    /**
     * @var bool  自定义分页大小数组
     */
    public $goPageSizeArr = ['10' => 10, '20' => 20, '50' => 50, '100' => 100, '200' => 200, '500' => 500, '1000' => 1000,'100000' => '全部'];

    /**
     *@var array.options about the goPageLabel(input)
     *goPageLabelOptions => [
     *       'class' =>
     *       'data-num' =>
     *       'style' =>
     *   ]
     *
     */
    public $goPageLabelOptions = [];

    /**
     *@var boole | string. weather show in go page button
     *
     */
    public $goButtonLable = false;

    /**
     *@var array.options about the goButton
     *
     */
    public $goButtonLableOptions = [];

    /**
     *
     **/
    public function init() {
        parent::init();

    }

    public function run()
    {
        if ($this->registerLinkTags) {
            $this->registerLinkTags();
        }
        echo $this->renderPageButtons();
    }

    protected function renderPageButtons() {

        $pageCount = $this->pagination->getPageCount();
        if ($pageCount < 2 && $this->hideOnSinglePage) {
            return '';
        }

        $buttons = [];
        $currentPage = $this->pagination->getPage();
        // first page
        $firstPageLabel = $this->firstPageLabel === true ? '1' : $this->firstPageLabel;
        if ($firstPageLabel !== false) {
            $buttons[] = $this->renderPageButton($firstPageLabel, 0, $this->firstPageCssClass, $currentPage <= 0, false);
        }

        // prev page
        if ($this->prevPageLabel !== false) {
            if (($page = $currentPage - 1) < 0) {
                $page = 0;
            }
            $buttons[] = $this->renderPageButton($this->prevPageLabel, $page, $this->prevPageCssClass, $currentPage <= 0, false);
        }

        // internal pages
        list($beginPage, $endPage) = $this->getPageRange();
        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->renderPageButton($i + 1, $i, null, false, $i == $currentPage);
        }

        // next page
        if ($this->nextPageLabel !== false) {
            if (($page = $currentPage + 1) >= $pageCount - 1) {
                $page = $pageCount - 1;
            }
            $buttons[] = $this->renderPageButton($this->nextPageLabel, $page, $this->nextPageCssClass, $currentPage >= $pageCount - 1, false);
        }

        // last page
        $lastPageLabel = $this->lastPageLabel === true ? $pageCount : $this->lastPageLabel;
        if ($lastPageLabel !== false) {
            $buttons[] = $this->renderPageButton($lastPageLabel, $pageCount - 1, $this->lastPageCssClass, $currentPage >= $pageCount - 1, false);
        }

        // total page
        if ($this->totalPageLable) {
            $buttons[] = Html::tag('li',Html::a(str_replace('x',$pageCount,$this->totalPageLable),'javascript:return false;',[]),[]);
        }

        //gopage
        if ($this->goPageLabel) {
            $input = Html::dropDownList('pageSize',$this->pagination->pageParam,$this->goPageSizeArr,array_merge([
                //'min' => 1,
                'prompt' => '请选择分页大小',
                'style' => 'height:34px;width:150px;display:inline-block;margin:0 3px 0 3px',
                'class' => 'form-control',
            ],$this->goPageLabelOptions));
            $buttons[] = Html::tag('li',$input,[]);
        }

        // gobuttonlink
        if ($this->goPageLabel) {
            $buttons[] = Html::submitInput($this->goButtonLable ? $this->goButtonLable : '跳转',array_merge([
                'style' => 'height:34px;display:inline-block;',
                'class' => 'btn btn-primary'
            ],$this->goButtonLableOptions));
        }
        $ul = Html::tag('ul', implode("\n", $buttons), $this->options);
        return Html::tag('form',$ul,[]);
    }
}



