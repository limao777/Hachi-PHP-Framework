<?php
/**
 * 发送邮件
 */
class Lib_Mail_SendMail
{

	public static function sendmail($to, $from, $fromdesc, $subject, $plaintext, $content) {
		static $phpmailer = NULL;

		if ( $phpmailer == NULL ) {
			$phpmailer = new Mail_PHPMailer();
		}

        $phpmailer->ClearAddresses();

		if ( !is_array($to) ) {
			$to = array($to);
		}

		try {
            $phpmailer->CharSet = "UTF-8";

            $smtpServer = Config::get('smtp_server');
            if ($smtpServer) {
                $phpmailer->IsSMTP();
                $phpmailer->SMTPAuth = false;
                $phpmailer->Host = $smtpServer;
            } else {
              $phpmailer->IsSendmail();
            }

            $phpmailer->SetFrom($from, "=?UTF-8?B?".base64_encode($fromdesc)."?=");
            foreach ( $to as $dest ) {
                $destname = @ explode('@', $dest);
                $destname = $destname[0];
                $phpmailer->AddAddress($dest, "=?UTF-8?B?".base64_encode($destname)."?=");
            }
            $phpmailer->Subject = "=?UTF-8?B?".base64_encode($subject)."?=";
            $phpmailer->AltBody = $plaintext;
            $phpmailer->MsgHTML($content);
            $phpmailer->Send();
            return TRUE;
		} catch (phpmailerException $e) {
		    return FALSE;
		} catch (Exception $e) {
		    return FALSE;
		}

		return TRUE;
	}
	
	public static function sendsmtp($to, $from, $fromdesc, $subject, $plaintext, $content, $host, $username, $password, $port) {
	    static $phpmailer = NULL;
	    if ( $phpmailer == NULL ) {
	        $phpmailer = new Mail_PHPMailer();
	    }
	    $phpmailer->ClearAddresses();
	    if ( !is_array($to) ) {
	        $to = array($to);
	    }
	    
	    try {
	        $phpmailer->CharSet = "UTF-8";
	        $phpmailer->IsSMTP();
	        $phpmailer->SMTPAuth = TRUE;
	        $phpmailer->Host = $host;
	        $phpmailer->Username = $username;
	        $phpmailer->Password = $password;
	        $phpmailer->Port = $port;
	        
	        $phpmailer->SetFrom($from, "=?UTF-8?B?".base64_encode($fromdesc)."?=");
	        foreach ( $to as $dest ) {
	            $destname = @ explode('@', $dest);
	            $destname = $destname[0];
	            $phpmailer->AddAddress($dest, "=?UTF-8?B?".base64_encode($destname)."?=");
	        }
	        $phpmailer->Subject = "=?UTF-8?B?".base64_encode($subject)."?=";
	        $phpmailer->AltBody = $plaintext;
	        $phpmailer->MsgHTML($content);
	        $phpmailer->Send();
	        return TRUE;
	    }
	    catch (phpmailerException $e) {
	        return FALSE;
	    } catch (Exception $e) {
	        return FALSE;
	    }
	    
	    return TRUE;
	}
}
