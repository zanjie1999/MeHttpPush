<?php
/**
 * Typecho 新评论给站长使用HTTP Api推送的插件
 *
 * @package MeHttpPush
 * @version 1.0
 * @author zyyme
 * @link https://zyyme.com
 */
class MeHttpPush_Plugin implements Typecho_Plugin_Interface {
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {

        Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'mePush');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array(__CLASS__, 'mePush');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback = array(__CLASS__, 'mePush');

        return _t('请点击设置，配置 HTTP推送地址');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        $key = new Typecho_Widget_Helper_Form_Element_Textarea('meHttpPushUrl', NULL, NULL, _t('HTTP推送地址'), _t('一行一个，消息会拼在最后或替换{}占位符，中文建议先进行urlencode再贴进来'));
        $form->addInput($key->addRule('required', _t('必须填写 HTTP推送地址 否则请禁用插件')));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 发送推送
     *
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function mePush($comment, $post) {
        $urlstr = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->meHttpPushUrl;
        $urls = explode("\n",$urlstr);

        $msg = rawurlencode($comment['author'].'在「'.$post->title.'」评论：'.$comment['text']);
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            } else if (strpos($url, '{}') !== false) {
                $url = str_replace('{}', $comment['text'], $url);
                file_get_contents($url);
            } else {
                file_get_contents($url.$msg);
            }
        }
        return  $comment;
    }
}
