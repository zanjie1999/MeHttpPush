<?php
/**
 * Typecho 新评论给站长使用HTTP Api推送的插件 兼reCAPTCHAv2验证码
 *
 * @package MeHttpPush
 * @version 2.0
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
        Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'filter');
        return _t('请点击设置进行配置');
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
        $key = new Typecho_Widget_Helper_Form_Element_Textarea('meHttpPushUrl', NULL, NULL, _t('HTTP推送地址'), _t('一行一个，消息会拼在最后或替换{}占位符，中文建议先进行urlencode再贴进来，留空则不启用该功能'));
        // $form->addInput($key->addRule('required', _t('必须填写 HTTP推送地址 否则请禁用插件')));
		$siteKey = new Typecho_Widget_Helper_Form_Element_Text('siteKey', NULL, '', _t('reCAPTCHAv2 的 Site Key:'), _t("需要在评论区加入 ".htmlspecialchars("<?php MeHttpPush_Plugin::auth();?>")." ，留空则不启用该功能"));
		$secretKey = new Typecho_Widget_Helper_Form_Element_Text('secretKey', NULL, '', _t('reCAPTCHAv2 的 Serect Key:'), _t("Key在这里申请 <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>"));
        $formId = new Typecho_Widget_Helper_Form_Element_Text('formId', NULL, '', _t('验证通过自动提交的form的id:'), _t("默认是 comment-form ，用了自动提交就可以去掉提交按钮了"));
        $form->addInput($key);
        $form->addInput($siteKey);
		$form->addInput($secretKey);
		$form->addInput($formId);
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
     * @return array
     */
    public static function mePush($comment, $post) {
        $urlstr = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->meHttpPushUrl;

        // 空就禁用此功能
        if ($urlstr == "") {
            return $comment;
        }

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

    /**
     * 展示reCAPTCHA验证码
     */
    public static function auth() {
        $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->siteKey;
        $secretKey = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->secretKey;
        $formId = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->formId;
        if ($formId == '') { $formId = 'comment-form'; }
        if ($siteKey != "" && $secretKey != "") {
            echo '<script src="https://recaptcha.net/recaptcha/api.js" async defer data-no-instant></script>
            <script>function meSubmit(){
                document.getElementById("'. $formId .'").submit();
            }</script>
            <div class="g-recaptcha" data-sitekey="' . $siteKey . '" data-callback="meSubmit"></div>';
        } else {
            throw new Typecho_Widget_Exception(_t('请先设置 reCAPTCHA 的 Site/Secret Keys!'));
        }
      }

    /**
     * 验证reCAPTCHA
     *
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return array
     */
    public static function filter($comments, $post) {
        $userObj = $post->widget('Widget_User');
        if($userObj->hasLogin() && $userObj->pass('administrator', true)) {
            // 管理员
            return $comments;
        } elseif (isset($_POST['g-recaptcha-response'])) {
            $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->siteKey;
            $secretKey = Typecho_Widget::widget('Widget_Options')->plugin('MeHttpPush')->secretKey;
            $resp = file_get_contents("https://recaptcha.net/recaptcha/api/siteverify?secret=".$secretKey."&response=".$_POST['g-recaptcha-response']);
            $resp = json_decode($resp);
            if ($resp->success == true) {
                // 评论成功
                return self::mePush($comments, $post);
            } else {
                switch ($resp->error-codes) {
                case '{[0] => "timeout-or-duplicate"}':
                    throw new Typecho_Widget_Exception(_t('请先完成验证'));
                    break;
                case '{[0] => "invalid-input-secret"}':
                    throw new Typecho_Widget_Exception(_t('博主填了无效的siteKey或者secretKey...'));
                    break;
                case '{[0] => "bad-request"}':
                        throw new Typecho_Widget_Exception(_t('请求错误！请检查网络'));
                        break;
                default:
                    throw new Typecho_Widget_Exception(_t('验证失败！本站不欢迎机器人'));
                }
            }
        } elseif ($siteKey == "" && $secretKey == "") {
            // 功能禁用
            return self::mePush($comments, $post);
        } else {
            throw new Typecho_Widget_Exception(_t('未成功加载验证码！请启用JavaScript'));
        }
    }
}
