<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 2.0.0
 * @name ComponentMailing
 * @file ComponentMailing.php
 * @date 30-06-2018 07:58 (SPAIN)
 */

class ComponentMailing
{
    private $fromTitle;
    private $emailFrom;
    private $emailsTo;
    private $emailsCc;
    private $emailsBcc;

    private $subject;
    private $content;
    private $header;

    private $headers;

    private $isSmtpUse;
    private $smtpHost;
    private $smtpPort;
    private $isSmtpAuth;
    private $smtpUser;
    private $smtpPassw;

    private $pathAttachment;

    /**
     *
     * @param string|array $mxEmailTo array tipo array(email1,email2...)
     * @param string $sSubject
     * @param string|array $mxContent array tipo $arLines = array("line text 1","line text 2"..) or string
     */
    public function __construct($emailTo = "", $subject = "", $content = "")
    {
        parent::__construct();
        $this->fromTitle = "";
        if (defined("APP_MAIL_ISSMTP") && APP_MAIL_ISSMTP !== "") {
            $this->isSmtpUse = APP_MAIL_ISSMTP;
        }
        if (defined("APP_MAIL_SMTP_HOST") && APP_MAIL_SMTP_HOST !== "") {
            $this->smtpHost = APP_MAIL_SMTP_HOST;
        }
        if (defined("APP_MAIL_SMTP_PORT") && APP_MAIL_SMTP_PORT !== "") {
            $this->smtpPort = APP_MAIL_SMTP_PORT;
        }
        if (defined("APP_MAIL_SMTP_AUTH") && APP_MAIL_SMTP_AUTH !== "") {
            $this->isSmtpAuth = APP_MAIL_SMTP_AUTH;
        }
        if (defined("APP_MAIL_SMTP_USER") && APP_MAIL_SMTP_USER !== "") {
            $this->smtpUser = APP_MAIL_SMTP_USER;
        }
        if (defined("APP_MAIL_SMTP_PASSW")) {
            $this->smtpPassw = APP_MAIL_SMTP_PASSW;
        }
        if ($this->smtpUser) {
            $this->emailFrom = $this->smtpUser;
        }

        $this->emailsTo = array();
        $this->headers = array();
        $this->headers[] = "MIME-Version: 1.0";
        //$this->arHeaders[] = "Content-Type: text/html; charset=ISO-8859-1";
        $this->headers[] = "Content-Type: text/html; charset=UTF-8";
        //add boundary string and mime type specification
        $this->_header[] = "Content-Transfer-Encoding: 8bit";
        if ($emailTo) {
            $this->emailsTo[] = $emailTo;
        }
        $this->subject = $subject;

        if (is_array($content)) {
            $content = implode(PHP_EOL, $content);
        }
        $this->content = $content;

        //esto recupera datos de: AppComponentExtconfig
        if (class_exists("AppComponentExtconfig")) {
            /*   ray(  "host" => "smtp.packprotecciononline.es",
                    "port" => "587",
                    "email_username" => "noreply@packprotecciononline.es",
                    "email_password" =>  "q4hHJ3R1",
                    "email_source" => "noreply@packprotecciononline.es",
                    'mailer'=>'smtp',
                    'smtppauth'=>true,
                    'smtpsecure' => 'tls'*/
            $this->smtpHost = AppComponentExtconfig::get_emailzurich1("host");
            $this->smtpUser = AppComponentExtconfig::get_emailzurich1("email_username");
            $this->smtpPassw = AppComponentExtconfig::get_emailzurich1("email_password");
            $this->smtpProtocol = AppComponentExtconfig::get_emailzurich1("mailer");
            $this->smtpPort = AppComponentExtconfig::get_emailzurich1("port");
            $this->isSmtpAuth = AppComponentExtconfig::get_emailzurich1("smtppauth");
            $this->smtpSecure = AppComponentExtconfig::get_emailzurich1("smtpsecure");
        }
    }//__construct

    private function sendPear()
    {
        $this->log_email("send_pear()", __CLASS__);
        //errorson();
        //necesita tener instalado:
        // PEAR https://pear.php.net/manual/en/installation.checking.php
        // Mail,Mail_Mime,Net_SMTP,Net_Socket
        // sudo pear install <pack> --alldeps

        //C:\xampp\php>pear list
        require_once("Mail.php");
        require_once("Mail/mime.php");

        $smtps = array();
        $smtps["host"] = $this->smtpHost;
        $smtps["port"] = $this->smtpPort;
        $smtps["auth"] = $this->isSmtpAuth;
        $smtps["username"] = $this->smtpUser;
        $smtps["password"] = $this->smtpPassw;
        //$arSmtp["debug"] = 1;
        //bug($arSmtp,"arSmtp");die;
        try {
            $smtp = Mail::factory("smtp", $smtps);

            $headers = array();
            $headers["Content-Type"] = "text/html; charset=UTF-8";
            //$arHeaders["From"] = $this->sEmailFrom;
            if (is_array($this->emailsTo)) {
                $this->emailsTo = implode(", ", $this->emailsTo);
            }
            $headers["To"] = $this->emailsTo;
            if ($this->emailsCc) {
                $headers["Cc"] = implode(", ", $this->emailsCc);
            }
            if ($this->emailsBcc) {
                $bcc = ", ".implode(", ", $this->emailsBcc);
            }
            $headers["Subject"] = $this->subject;
            //bug($arHeaders);die;
            $headers["From"] = $this->emailFrom;

            $mime = new Mail_mime(array("eol" => PHP_EOL));
            //$oMime->setTXTBody("texto body"); //texto sin html
            $mime->setHTMLBody($this->content); //texto con html

            if ($this->pathAttachment) {
                $mime->addAttachment($this->pathAttachment, "text/plain");
            }

            $mimes = array(
              "text_encoding" => "7bit",
              "text_charset" => "UTF-8",
              "html_charset" => "UTF-8",
              "head_charset" => "UTF-8"
            );

            //do not ever try to call these lines in reverse order
            $content = $mime->get($mimes);
            $headers = $mime->headers($headers);
            //la única forma de enviar con copia oculta es añadirlo a los receptores
            $emailsTo = $headers["To"].$bcc;
            //->send es igual a: mail($recipients, $subject,$body,$text_headers);
            $email = $smtp->send($emailsTo, $headers, $content);

            if (PEAR::isError($email)) {
                $this->addError($email->getMessage());
            }
        } catch(Exception $ex) {
            $this->addError($ex->getMessage());
        }
        //bug($this->arErrorMessages);die;
        return $this->isError;
    }//send_smtp

    /**
     * uses function mail(...)
     * @return boolean
     */
    private function sendNosmtp()
    {
        $this->log_email("send_nosmtp()", __CLASS__);
        $this->buildHeaderFrom();
        $this->buildHeaderCc();//crea header: Cc
        $this->buildHeaderBcc();//crea header: Bcc
        //crea los header en $this->_header
        $this->buildHeader();

        $this->log_email("mailsto:$this->emailsTo,subject:$this->subject,header:$this->header", __CLASS__."send_nosmtp()");
        if ($this->emailsTo) {
            if (is_array($this->emailsTo)) {
                $this->emailsTo = implode(", ", $this->emailsTo);
            }

            //TRUE if success
            /*
            telnet 127.0.0.1 25
            220 eduardosvc ESMTP Sendmail 8.14.4/8.14.4/Debian-4.1ubuntu1; Thu, 25 Feb 2016 10:47:44 +0100;
            (No UCE/UBE) logging access from: caser.loc(OK)-caser.loc [127.0.0.1]
            */
            $this->log_email("antes de llamar a funcion mail", __CLASS__);
            //$this->log_email("mailsto:$this->mxEmailsTo,subject:$this->sSubject,content:$this->sContent,header:$this->sHeader");
            $status = mail($this->emailsTo, $this->subject, $this->content, $this->header);
            $this->log_email($status, __CLASS__." status mail(..)");
            if ($status == false) {
                $this->addError("Error sending email!");
            }
        } else {
            $this->addError("No target emails!");
        }
        return $this->isError;
    }//send_nosmtp


    /**
     * Utiliza la funcion mail. Se puede recuperar el error con $this->get_error_message();
     * @return boolean TRUE if error occurred
     */
    public function send()
    {
        if ($this->isSmtpUse) {
            return $this->sendPear();
        }
        return $this->sendNosmtp();
    }

    private function buildHeader()
    {
        $header = implode(PHP_EOL, $this->headers);
        $this->header = $header;
    }

    private function buildHeaderFrom()
    {
        if ($this->emailFrom) {
            $this->headers[] = "From: $this->fromTitle <$this->emailFrom>";
            $this->headers[] = "Return-Path: <$this->emailFrom>";
            $this->headers[] = "X-Sender: $this->emailFrom";
        }
    }

    private function buildHeaderCc()
    {
        if ($this->emailsCc) {
            $this->headers[] = "Cc: ".implode(", ", $this->emailsCc);
        }
    }

    private function buildHeaderBcc()
    {
        if ($this->emailsBcc) {
            $this->headers[] = "Bcc: ".implode(", ", $this->emailsBcc);
        }
    }

    //**********************************
    //             SETS
    //**********************************
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
    public function setEmailFrom($email)
    {
        $this->emailFrom = $email;
    }
    public function setEmailsTo($emails)
    {
        $this->emailsTo = $emails;
    }
    public function addEmailTo($email)
    {
        $this->emailsTo[] = $email;
    }
    public function setEmailsCc($emails)
    {
        $this->emailsCc = $emails;
    }
    public function addEmailCc($email)
    {
        $this->emailsCc[] = $email;
    }
    public function setEmailsBcc($emails)
    {
        $this->emailsBcc = $emails;
    }
    public function addEmailBcc($email)
    {
        $this->emailsBcc[] = $email;
    }
    public function setHeader($header)
    {
        $this->header = $header;
    }
    public function setContent($content)
    {
        (is_array($content)) ? $this->content = implode(PHP_EOL, $content) : $this->content = $content;
    }
    public function setTitleFrom($title)
    {
        $this->fromTitle = $title;
    }

    /**
     *  Required
        "MIME-Version: 1.0"
        "Content-type: text/html; charset=iso-8859-1"
        Optional
     *  "From: Recordatorio <cumples@example.com>"
        "To: Mary <mary@example.com>, Kelly <kelly@example.com>"
        "Cc: birthdayarchive@example.com"
        "Bcc: birthdaycheck@example.com"
     * mail($to,$subject,$message,$headers);
     *
     * @param string $sHeader Cualquer linea anterior
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;
    }
    public function clearHeaders()
    {
        $this->headers = array();
    }

    public function setSmtpUse($isOn = true)
    {
        $this->isSmtpUse = $isOn;
    }
    public function setSmtpHost($value)
    {
        $this->smtpHost = $value;
    }
    public function setSmtpPort($value)
    {
        $this->smtpPort = $value;
    }
    public function setSmtpAuth($isOn = true)
    {
        $this->isSmtpAuth = $isOn;
    }
    public function setSmtpUser($value)
    {
        $this->smtpUser = $value;
    }
    public function setSmtpPassw($value)
    {
        $this->smtpPassw = $value;
    }
    //**********************************
    //             GETS
    //**********************************

}//ComponentMailing
