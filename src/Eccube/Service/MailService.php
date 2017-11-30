<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Eccube\Service;

use Eccube\Annotation\Inject;
use Eccube\Annotation\Service;
use Eccube\Application;
use Eccube\Entity\BaseInfo;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\MailTemplateRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @Service
 */
class MailService
{
    /**
     * @Inject(MailTemplateRepository::class)
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @Inject("eccube.event.dispatcher")
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @Inject(BaseInfo::class)
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @Inject(Application::class)
     * @var Application
     */
    protected $app;

    /**
     * Send customer confirm mail.
     *
     * @param $Customer 会員情報
     * @param $activateUrl アクティベート用url
     */
    public function sendCustomerConfirmMail(\Eccube\Entity\Customer $Customer, $activateUrl)
    {

        log_info('仮会員登録メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['entry_confirm_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Customer' => $Customer,
            'BaseInfo' => $this->BaseInfo,
            'activateUrl' => $activateUrl,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Customer->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'activateUrl' => $activateUrl,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CUSTOMER_CONFIRM, $event);

        $count = $this->app->mail($message, $failures);

        log_info('仮会員登録メール送信完了', array('count' => $count));

        return $count;
    }

    /**
     * Send customer complete mail.
     *
     * @param $Customer 会員情報
     */
    public function sendCustomerCompleteMail(\Eccube\Entity\Customer $Customer)
    {
        log_info('会員登録完了メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['entry_complete_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Customer' => $Customer,
            'BaseInfo' => $this->BaseInfo,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Customer->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CUSTOMER_COMPLETE, $event);

        $count = $this->app->mail($message);

        log_info('会員登録完了メール送信完了', array('count' => $count));

        return $count;
    }


    /**
     * Send withdraw mail.
     *
     * @param $Customer 会員情報
     * @param $email 会員email
     */
    public function sendCustomerWithdrawMail(\Eccube\Entity\Customer $Customer, $email)
    {
        log_info('退会手続き完了メール送信開始');
        
        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['customer_withdraw_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Customer' => $Customer,
            'BaseInfo' => $this->BaseInfo,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($email))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'email' => $email,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CUSTOMER_WITHDRAW, $event);

        $count = $this->app->mail($message);

        log_info('退会手続き完了メール送信完了', array('count' => $count));

        return $count;
    }


    /**
     * Send contact mail.
     *
     * @param $formData お問い合わせ内容
     */
    public function sendContactMail($formData)
    {
        log_info('お問い合わせ受付メール送信開始');
        
        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['contact_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'data' => $formData,
            'BaseInfo' => $this->BaseInfo,
        ));

        // 問い合わせ者にメール送信
        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail02() => $this->BaseInfo->getShopName()))
            ->setTo(array($formData['email']))
            ->setBcc($this->BaseInfo->getEmail02())
            ->setReplyTo($this->BaseInfo->getEmail02())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'formData' => $formData,
                'BaseInfo' => $this->BaseInfo,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_CONTACT, $event);

        $count = $this->app->mail($message);

        log_info('お問い合わせ受付メール送信完了', array('count' => $count));

        return $count;
    }

    /**
     * Alias of sendContactMail().
     *
     * @param $formData お問い合わせ内容
     * @see sendContactMail()
     * @deprecated since 3.0.0, to be removed in 3.1
     * @link https://github.com/EC-CUBE/ec-cube/issues/1315
     */
    public function sendrContactMail($formData)
    {
        $this->sendContactMail($formData);
    }

    /**
     * Send order mail.
     *
     * @param \Eccube\Entity\Order $Order 受注情報
     * @return string
     */
    public function sendOrderMail(\Eccube\Entity\Order $Order)
    {
        log_info('受注メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['order_mail_template_id']);

        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Order' => $Order,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getMailSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Order->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Order' => $Order,
                'MailTemplate' => $MailTemplate,
                'BaseInfo' => $this->BaseInfo,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_ORDER, $event);

        $count = $this->app->mail($message);

        log_info('受注メール送信完了', array('count' => $count));

        return $message;

    }


    /**
     * Send admin customer confirm mail.
     *
     * @param $Customer 会員情報
     * @param $activateUrl アクティベート用url
     */
    public function sendAdminCustomerConfirmMail(\Eccube\Entity\Customer $Customer, $activateUrl)
    {
        log_info('仮会員登録再送メール送信開始');
        
        /* @var $MailTemplate \Eccube\Entity\MailTemplate */
        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['entry_confirm_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Customer' => $Customer,
            'activateUrl' => $activateUrl,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail03() => $this->BaseInfo->getShopName()))
            ->setTo(array($Customer->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'activateUrl' => $activateUrl,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_ADMIN_CUSTOMER_CONFIRM, $event);

        $count = $this->app->mail($message);

        log_info('仮会員登録再送メール送信完了', array('count' => $count));

        return $count;
    }


    /**
     * Send admin order mail.
     *
     * @param $Order 受注情報
     * @param $formData 入力内容
     */
    public function sendAdminOrderMail(\Eccube\Entity\Order $Order, $formData)
    {
        log_info('受注管理通知メール送信開始');

        $body = $this->app->renderView('Mail/order.twig', array(
            'header' => $formData['mail_header'],
            'footer' => $formData['mail_footer'],
            'Order' => $Order,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $formData['mail_subject'])
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Order->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Order' => $Order,
                'formData' => $formData,
                'BaseInfo' => $this->BaseInfo,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_ADMIN_ORDER, $event);

        $count = $this->app->mail($message);

        log_info('受注管理通知メール送信完了', array('count' => $count));

        return $count;
    }

    /**
     * Send password reset notification mail.
     *
     * @param $Customer 会員情報
     */
    public function sendPasswordResetNotificationMail(\Eccube\Entity\Customer $Customer, $reset_url)
    {
        log_info('パスワード再発行メール送信開始');
        
        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['forgot_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Customer' => $Customer,
            'reset_url' => $reset_url
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Customer->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'resetUrl' => $reset_url,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_PASSWORD_RESET, $event);

        $count = $this->app->mail($message);

        log_info('パスワード再発行メール送信完了', array('count' => $count));

        return $count;
    }

    /**
     * Send password reset notification mail.
     *
     * @param $Customer 会員情報
     */
    public function sendPasswordResetCompleteMail(\Eccube\Entity\Customer $Customer, $password)
    {
        log_info('パスワード変更完了メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->app['config']['reset_complete_mail_template_id']);
        
        $body = $this->app->renderView($MailTemplate->getFileName(), array(
            'header' => $MailTemplate->getMailHeader(),
            'footer' => $MailTemplate->getMailFooter(),
            'Customer' => $Customer,
            'password' => $password,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($Customer->getEmail()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $event = new EventArgs(
            array(
                'message' => $message,
                'Customer' => $Customer,
                'BaseInfo' => $this->BaseInfo,
                'password' => $password,
            ),
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_PASSWORD_RESET_COMPLETE, $event);

        $count = $this->app->mail($message);

        log_info('パスワード変更完了メール送信完了', array('count' => $count));

        return $count;
    }

    /**
     * ポイントでマイナス発生時にメール通知する。
     *
     * @param Order $Order
     * @param int $currentPoint
     * @param int $changePoint
     */
    public function sendPointNotifyMail(\Eccube\Entity\Order $Order, $currentPoint = 0, $changePoint = 0)
    {

        $body = $this->app->renderView('Mail/point_notify.twig', array(
            'Order' => $Order,
            'currentPoint' => $currentPoint,
            'changePoint' => $changePoint,
        ));

        $message = \Swift_Message::newInstance()
            ->setSubject('['.$this->BaseInfo->getShopName().'] ポイント通知')
            ->setFrom(array($this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()))
            ->setTo(array($this->BaseInfo->getEmail01()))
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $this->app->mail($message);
    }
}
