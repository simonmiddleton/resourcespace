<?php


$lang["checkmail_configuration"]='检查邮件配置';
$lang["checkmail_install_php_imap_extension"]='步骤一：安装 PHP IMAP 扩展。';
$lang["checkmail_cronhelp"]='此插件需要一些特殊设置，以便系统登录到专用于接收上传文件的电子邮件帐户。<br /><br />请确保帐户启用了IMAP。如果您使用的是Gmail帐户，则可以在“设置”->“POP / IMAP”->“启用IMAP”中启用IMAP。<br /><br />
在初始设置中，您可能会发现手动在命令行上运行plugins/checkmail/pages/cron_check_email.php插件最有帮助，以了解其工作原理。
一旦您正确连接并了解脚本的工作原理，您必须设置一个cron作业来每一两分钟运行一次。<br />它将扫描邮箱并每次运行读取一个未读电子邮件。<br /><br />
每两分钟运行一次的示例cron作业：<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='您的IMAP帐户上次检查时间为[lastcheck]。';
$lang["checkmail_cronjobprob"]='您的checkmail cronjob可能无法正常运行，因为距离上次运行已经超过5分钟。<br /><br />
一个每分钟运行一次的示例cron job：<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap服务器<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='电子邮件';
$lang["checkmail_password"]='密码';
$lang["checkmail_extension_mapping"]='资源类型通过文件扩展名映射';
$lang["checkmail_default_resource_type"]='默认资源类型';
$lang["checkmail_extension_mapping_desc"]='默认资源类型选择器后，每个资源类型下面都有一个输入框。<br />如果要将不同类型的上传文件强制转换为特定的资源类型，请添加逗号分隔的文件扩展名列表（例如：jpg、gif、png）。';
$lang["checkmail_subject_field"]='主题字段';
$lang["checkmail_body_field"]='正文字段';
$lang["checkmail_purge"]='上传后清除电子邮件？';
$lang["checkmail_confirm"]='发送确认电子邮件？';
$lang["checkmail_users"]='允许的用户';
$lang["checkmail_blocked_users_label"]='被阻止的用户';
$lang["checkmail_default_access"]='默认访问';
$lang["checkmail_default_archive"]='默认状态';
$lang["checkmail_html"]='允许HTML内容？（实验性的，不建议使用）';
$lang["checkmail_mail_skipped"]='跳过的电子邮件 (Tiào guò de diànzǐ yóujiàn)';
$lang["checkmail_allow_users_based_on_permission_label"]='用户是否应该根据权限被允许上传？';
$lang["addresourcesviaemail"]='通过电子邮件添加';
$lang["uploadviaemail"]='通过电子邮件添加';
$lang["uploadviaemail-intro"]='通过电子邮件上传，请附上您的文件并将电子邮件发送至<b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>。</p> <p>请务必从<b>[fromaddress]</b>发送，否则将被忽略。</p><p>请注意，电子邮件主题中的任何内容都将进入%applicationname%中的[subjectfield]字段。</p><p>还请注意，电子邮件正文中的任何内容都将进入%applicationname%中的[bodyfield]字段。</p> <p>多个文件将被分组到一个集合中。您的资源将默认为访问级别<b>\'[access]\'</b>和归档状态<b>\'[archive]\'</b>。</p><p>[confirmation]</p>';
$lang["checkmail_confirmation_message"]='当您的电子邮件成功处理后，您将收到一封确认电子邮件。如果您的电子邮件由于某种原因（例如从错误的地址发送）被程序自动跳过，则管理员将收到通知，以便处理需要关注的电子邮件。';
$lang["yourresourcehasbeenuploaded"]='您的资源已上传。';
$lang["yourresourceshavebeenuploaded"]='您的资源已上传。';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username])，ID为[user-ref]，电子邮件为[user-email]，不允许通过电子邮件上传（请检查权限“c”或“d”或在checkmail设置页面中查看被阻止的用户）。记录时间：[datetime]。';
$lang["checkmail_createdfromcheckmail"]='从“检查邮件”插件创建';