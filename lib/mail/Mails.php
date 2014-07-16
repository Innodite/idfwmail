<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Mails
 *
 * @author merma158 <jurbano@innodite.com en Innodite, C.A.>
 */
class Mails extends Main{
    //put your code here
    private $host = "{mail.innodite.com:143/novalidate-cert}";
    private $user /*= "jurbano@innodite.com"*/;
    private $pass /*= "jut.120388"*/;
    private $mailbox = "INBOX";
    private $codes = array("7bit", "8bit", "binary", "base64", "quoted-printable", "other");
    private $stt = array("Text", "Multipart", "Message", "Application", "Audio", "Image", "Video", "Other");
    
    static public $aliases = array(
        'USASCII'       => 'WINDOWS-1252',
        'ANSIX31101983' => 'WINDOWS-1252',
        'ANSIX341968'   => 'WINDOWS-1252',
        'UNKNOWN8BIT'   => 'ISO-8859-15',
        'UNKNOWN'       => 'ISO-8859-15',
        'USERDEFINED'   => 'ISO-8859-15',
        'KSC56011987'   => 'EUC-KR',
        'GB2312'        => 'GBK',
        'GB231280'      => 'GBK',
        'UNICODE'       => 'UTF-8',
        'UTF7IMAP'      => 'UTF7-IMAP',
        'TIS620'        => 'WINDOWS-874',
        'ISO88599'      => 'WINDOWS-1254',
        'ISO885911'     => 'WINDOWS-874',
        'MACROMAN'      => 'MACINTOSH',
        '77'            => 'MAC',
        '128'           => 'SHIFT-JIS',
        '129'           => 'CP949',
        '130'           => 'CP1361',
        '134'           => 'GBK',
        '136'           => 'BIG5',
        '161'           => 'WINDOWS-1253',
        '162'           => 'WINDOWS-1254',
        '163'           => 'WINDOWS-1258',
        '177'           => 'WINDOWS-1255',
        '178'           => 'WINDOWS-1256',
        '186'           => 'WINDOWS-1257',
        '204'           => 'WINDOWS-1251',
        '222'           => 'WINDOWS-874',
        '238'           => 'WINDOWS-1250',
        'MS950'         => 'CP950',
        'WINDOWS949'    => 'UHC',
    );
    
    public function __construct() {
        parent::__construct();
        $this->user = $this->user_mail;
        $this->pass = $this->user_pass;
    }
    
    public function __destruct() {
        parent::__destruct();
    }
    
    public function getContactsForTtip($p=null){
        return array("data"=>$this->contactosFile);
    }

   public function getImapInbox($p=null){
        $salida = array();
        
        $this->mailbox = (isset($p['folder']) && (strlen($p['folder']) > 0)) ? $p['folder'] : $this->mailbox;
        
        $inbox = imap_open($this->host.$this->mailbox, $this->user, $this->pass);
        if ($inbox){
            $emails = imap_search($inbox, 'ALL');
            if ($emails) {
                rsort($emails);
                foreach($emails as $email_number){
                    $message = "";
                    $header = imap_header($inbox, $email_number);
                    
                    $st = imap_fetchstructure($inbox, $email_number);
                    $multi  = $st->parts;
                    $n = count($multi);
                    // Decodificar Asunto en caso de ser necesario
                    switch ($this->codes[$st->encoding]){
                        case "base64": $asunto = imap_base64($header->subject); break;
                        case "8bit": $asunto = $this->decodificar_mensaje($header->subject); break;
                        case "quoted-printable": $asunto = imap_qprint($header->subject); break;
                        default: $asunto = $header->subject; break;
                    }
                    $mj = array();
                    $sw = false;
                    for ($i=0; $i <= $n; $i++){
                        if ($i == $n){ $sw = true; }
                        $idx = ($i<$n) ? $i : $i-1;
                        $idt = ($idx == 1 && !$sw) ? 0 : $idx;
                        array_push($mj, array("mimetype"=> strtolower($this->stt[$multi[$idt]->type])."/".strtolower($multi[$idt]->subtype),
                                              "mensaje"=>imap_fetchbody($inbox, $email_number, $i),
                                              "indice"=>$i,
                                              "sonecode"=>$this->codes[$multi[$idx]->encoding],
                                              "phrecode"=>$this->codes[$st->encoding]));
                    }
                    if (count($mj)>0){
                        foreach ($mj as $key => $value){
                            switch ($value['sonecode']){
                                case "base64": $message = imap_base64($value['mensaje']); break;
                                case "8bit":   $message = $this->decodificar_mensaje($value['mensaje']); break;
                                case "quoted-printable": $message = imap_qprint($value['mensaje']); break;
                                default: $message = $value['mensaje']; break;
                            }
                            if ($value['mimetype'] != "text/plain"){ break; }
                        }
                    }
                    array_push($salida, array("asunto"=>$asunto,
                                              "fecha"=>date("Y-m-d"),
                                              "date"=>$header->date,
                                              "from"=>$header->fromaddress,
                                              "to"=>$header->toaddress,
                                              "cc"=>$header->ccaddress,
                                              "co"=>$header->bccaddress,
                                              "body"=>$message,
                                              "size"=>$header->Size));   
                }
            }
            imap_close($inbox);
        }
        return array("data"=>$salida);
    }
    
    public function sendSmtpMail($p=null){
        $exito = false;
        $errormsg = "";
        header("Content-Type: text/html; charset=ISO-8859-1", true);
        if ($import = $this->importarClase("extras.PHPMailer.phpmailer")){
            $server = array("ssl" => "b010.dnsprincipal.com", "nossl" => "mail.innodite.com");
            $port = array("ssl-imap" => 993, "ssl-pop3" => 995, "ssl-smtp" => 465, "nossl-imap" => 143, "nossl-pop3" => 110, "nossl-smtp" => 9026);
            
            if (is_array($import) || $import){
                
                $mail = new PHPMailer(true);
                $mail->IsSMTP();
                $mail->setLanguage("es");
                $mail->CharSet = 'UTF-8';

                $mail->Host = $server['nossl'];       //SMTP server example
                $mail->SMTPDebug = 0;                //enables SMTP debug information (for testing)
                $mail->SMTPAuth = true;              //enable SMTP authentication
                $mail->Port = $port['nossl-smtp'];   // set the SMTP port for the GMAIL server
                $mail->Username = $this->user;      // SMTP account username example
                $mail->Password = $this->pass;      // SMTP account password example
                
                
                try{
                    //Indicamos cual es nuestra dirección de correo y el nombre que 
                    //queremos que vea el usuario que lee nuestro correo
                    $mail->From = $this->user;
                    $mail->FromName = $this->user_pref[0];

                    //el valor por defecto 10 de Timeout es un poco escaso dado que voy a usar 
                    //una cuenta gratuita, por tanto lo pongo a 120  
                    $mail->Timeout = 120;
                    
                    //Indicamos cual es la dirección de destino del correo
                    $TO = $this->prepareMailtxt($p['txtto']);
                    if (count($TO)>0) {
                        foreach ($TO as $key => $value) { $mail->AddAddress(trim($value['mail']),trim($value['name'])); }
                    }
                    // *********************************************************
                    $CC = $this->prepareMailtxt($p['txtcc']);
                    if(count($CC)>0){
                        foreach ($CC as $key => $value) { $mail->addCC(trim($value['mail']),trim($value['name'])); }
                    }
                    // *********************************************************
                    $CO = $this->prepareMailtxt($p['txtco']);
                    if (count($CO)>0){
                        foreach ($CO as $key => $value) { $mail->addBCC(trim($value['mail']),trim($value['name'])); }
                    }
                    //Asignamos asunto y cuerpo del mensaje
                    //El cuerpo del mensaje lo ponemos en formato html
                    $mail->Subject = trim($p['txtsg']);
                    $mail->Body = trim($p['txtmghtml']);

                    //Definimos AltBody por si el destinatario del correo no admite email con formato html 
                    $mail->AltBody = trim($p['txtmgtext']);

                    //se envia el mensaje, si no ha habido problemas 
                    //la variable $exito tendra el valor true
                    $mail->isHTML(true);
                    $exito = $mail->Send();
                }catch (phpmailerException $e){ $exito = false; $errormsg = $e->errorMessage(); }
                if($exito){
                    $this->mailbox = "INBOX.Sent";
                    $inbox = imap_open($this->host.$this->mailbox, $this->user, $this->pass);
                    imap_append($inbox, $this->host.$this->mailbox, $mail->getMIMEHedaer().$mail->getMIMEBody()); //, "\\Seen"
                    imap_close($inbox);
                }
            }
        }
        
        return array("data"=> $exito ? "exito" : "no-found","errormsg"=>$errormsg);
    }
    
    public function prepareMailtxt($p) {
        $out = array();
        foreach (explode(";", trim($p)) as $key => $value) {
            $data = explode("|", $value);
            if (count($data)>1){
                $out[] = array("mail"=>str_replace(">", "", str_replace("<", "", trim($data[1]))),
                               "name"=>str_replace('"', '', trim($data[0])));
            }
            // *****************************************************************
            if (strlen($value)>0 && strpos($value, "|") === false){
                $out[] = array("mail"=>str_replace(">", "", str_replace("<", "", trim($value))),
                               "name"=>'');
            }
        }
        return $out;
    }
    
    public function decodificar_mensaje($input,$codchar="UTF-8"){
       
       $default_charset = $codchar;
       $input = preg_replace("/\?=\s+=\?/", '?==?', $input);
       // encoded-word regexp
        $re = '/=\?([^?]+)\?([BbQq])\?([^\n]*?)\?=/';
        // Find all RFC2047's encoded words
        if (preg_match_all($re, $input, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            // Initialize variables
            $tmp   = array();
            $out   = '';
            $start = 0;

            foreach ($matches as $idx => $m) {
                $pos      = $m[0][1];
                $charset  = $m[1][0];
                $encoding = $m[2][0];
                $text     = $m[3][0];
                $length   = strlen($m[0][0]);

                // Append everything that is before the text to be decoded
                if ($start != $pos) {
                    $substr = substr($input, $start, $pos-$start);
                    $out   .= self::convert($substr, $default_charset);
                    $start  = $pos;
                }
                $start += $length;

                // Per RFC2047, each string part "MUST represent an integral number
                // of characters . A multi-octet character may not be split across
                // adjacent encoded-words." However, some mailers break this, so we
                // try to handle characters spanned across parts anyway by iterating
                // through and aggregating sequential encoded parts with the same
                // character set and encoding, then perform the decoding on the
                // aggregation as a whole.

                $tmp[] = $text;
                if ($next_match = $matches[$idx+1]) {
                    if ($next_match[0][1] == $start
                        && $next_match[1][0] == $charset
                        && $next_match[2][0] == $encoding
                    ) {
                        continue;
                    }
                }

                $count = count($tmp);
                $text  = '';

                // Decode and join encoded-word's chunks
                if ($encoding == 'B' || $encoding == 'b') {
                    // base64 must be decoded a segment at a time
                    for ($i=0; $i<$count; $i++)
                        $text .= base64_decode($tmp[$i]);
                }
                else { //if ($encoding == 'Q' || $encoding == 'q') {
                    // quoted printable can be combined and processed at once
                    for ($i=0; $i<$count; $i++)
                        $text .= $tmp[$i];

                    $text = str_replace('_', ' ', $text);
                    $text = quoted_printable_decode($text);
                }

                $out .= self::convert($text, $charset);
                $tmp = array();
            }

            // add the last part of the input string
            if ($start != strlen($input)) {
                $out .= self::convert(substr($input, $start), $default_charset);
            }

            // return the results
            return $out;
        }
    }
    
    public function parse_charset($input){
        static $charsets = array();
        $charset = strtoupper($input);

        if (isset($charsets[$input])) {
            return $charsets[$input];
        }

        $charset = preg_replace(array(
            '/^[^0-9A-Z]+/',    // e.g. _ISO-8859-JP$SIO
            '/\$.*$/',          // e.g. _ISO-8859-JP$SIO
            '/UNICODE-1-1-*/',  // RFC1641/1642
            '/^X-/',            // X- prefix (e.g. X-ROMAN8 => ROMAN8)
        ), '', $charset);

        if ($charset == 'BINARY') {
            return $charsets[$input] = null;
        }

        // allow A-Z and 0-9 only
        $str = preg_replace('/[^A-Z0-9]/', '', $charset);

        if (isset(self::$aliases[$str])) {
            $result = self::$aliases[$str];
        }
        // UTF
        else if (preg_match('/U[A-Z][A-Z](7|8|16|32)(BE|LE)*/', $str, $m)) {
            $result = 'UTF-' . $m[1] . $m[2];
        }
        // ISO-8859
        else if (preg_match('/ISO8859([0-9]{0,2})/', $str, $m)) {
            $iso = 'ISO-8859-' . ($m[1] ? $m[1] : 1);
            // some clients sends windows-1252 text as latin1,
            // it is safe to use windows-1252 for all latin1
            $result = $iso == 'ISO-8859-1' ? 'WINDOWS-1252' : $iso;
        }
        // handle broken charset names e.g. WINDOWS-1250HTTP-EQUIVCONTENT-TYPE
        else if (preg_match('/(WIN|WINDOWS)([0-9]+)/', $str, $m)) {
            $result = 'WINDOWS-' . $m[2];
        }
        // LATIN
        else if (preg_match('/LATIN(.*)/', $str, $m)) {
            $aliases = array('2' => 2, '3' => 3, '4' => 4, '5' => 9, '6' => 10,
                '7' => 13, '8' => 14, '9' => 15, '10' => 16,
                'ARABIC' => 6, 'CYRILLIC' => 5, 'GREEK' => 7, 'GREEK1' => 7, 'HEBREW' => 8
            );

            // some clients sends windows-1252 text as latin1,
            // it is safe to use windows-1252 for all latin1
            if ($m[1] == 1) {
                $result = 'WINDOWS-1252';
            }
            // if iconv is not supported we need ISO labels, it's also safe for iconv
            else if (!empty($aliases[$m[1]])) {
                $result = 'ISO-8859-'.$aliases[$m[1]];
            }
            // iconv requires convertion of e.g. LATIN-1 to LATIN1
            else {
                $result = $str;
            }
        }
        else {
            $result = $charset;
        }

        $charsets[$input] = $result;

        return $result;
    }
    /**
     * Converts string from UTF-16 to UTF-8 (helper for utf-7 to utf-8 conversion)
     *
     * @param  string  Input string
     *
     * @return string  The converted string
     */
    public static function utf16_to_utf8($str){
        $len = strlen($str);
        $dec = '';

        for ($i = 0; $i < $len; $i += 2) {
            $c = ord($str[$i]) << 8 | ord($str[$i + 1]);
            if ($c >= 0x0001 && $c <= 0x007F) {
                $dec .= chr($c);
            }
            else if ($c > 0x07FF) {
                $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
                $dec .= chr(0x80 | (($c >>  6) & 0x3F));
                $dec .= chr(0x80 | (($c >>  0) & 0x3F));
            }
            else {
                $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
                $dec .= chr(0x80 | (($c >>  0) & 0x3F));
            }
        }

        return $dec;
    }
    /**
     * Convert the data ($str) from RFC 2060's UTF-7 to UTF-8.
     * If input data is invalid, return the original input string.
     * RFC 2060 obviously intends the encoding to be unique (see
     * point 5 in section 5.1.3), so we reject any non-canonical
     * form, such as &ACY- (instead of &-) or &AMA-&AMA- (instead
     * of &AMAAwA-).
     *
     * Translated from C to PHP by Thomas Bruederli <roundcube@gmail.com>
     *
     * @param string $str Input string (UTF7-IMAP)
     *
     * @return string Output string (UTF-8)
     */
    public static function utf7imap_to_utf8($str){
        $Index_64 = array(
            -1,-1,-1,-1, -1,-1,-1,-1, -1,-1,-1,-1, -1,-1,-1,-1,
            -1,-1,-1,-1, -1,-1,-1,-1, -1,-1,-1,-1, -1,-1,-1,-1,
            -1,-1,-1,-1, -1,-1,-1,-1, -1,-1,-1,62, 63,-1,-1,-1,
            52,53,54,55, 56,57,58,59, 60,61,-1,-1, -1,-1,-1,-1,
            -1, 0, 1, 2,  3, 4, 5, 6,  7, 8, 9,10, 11,12,13,14,
            15,16,17,18, 19,20,21,22, 23,24,25,-1, -1,-1,-1,-1,
            -1,26,27,28, 29,30,31,32, 33,34,35,36, 37,38,39,40,
            41,42,43,44, 45,46,47,48, 49,50,51,-1, -1,-1,-1,-1
        );

        $u7len = strlen($str);
        $str   = strval($str);
        $p     = '';
        $err   = '';

        for ($i=0; $u7len > 0; $i++, $u7len--) {
            $u7 = $str[$i];
            if ($u7 == '&') {
                $i++;
                $u7len--;
                $u7 = $str[$i];

                if ($u7len && $u7 == '-') {
                    $p .= '&';
                    continue;
                }

                $ch = 0;
                $k = 10;
                for (; $u7len > 0; $i++, $u7len--) {
                    $u7 = $str[$i];

                    if ((ord($u7) & 0x80) || ($b = $Index_64[ord($u7)]) == -1) {
                        break;
                    }

                    if ($k > 0) {
                        $ch |= $b << $k;
                        $k -= 6;
                    }
                    else {
                        $ch |= $b >> (-$k);
                        if ($ch < 0x80) {
                            // Printable US-ASCII
                            if (0x20 <= $ch && $ch < 0x7f) {
                                return $err;
                            }
                            $p .= chr($ch);
                        }
                        else if ($ch < 0x800) {
                            $p .= chr(0xc0 | ($ch >> 6));
                            $p .= chr(0x80 | ($ch & 0x3f));
                        }
                        else {
                            $p .= chr(0xe0 | ($ch >> 12));
                            $p .= chr(0x80 | (($ch >> 6) & 0x3f));
                            $p .= chr(0x80 | ($ch & 0x3f));
                        }

                        $ch = ($b << (16 + $k)) & 0xffff;
                        $k += 10;
                    }
                }

                // Non-zero or too many extra bits
                if ($ch || $k < 6) {
                    return $err;
                }

                // BASE64 not properly terminated
                if (!$u7len || $u7 != '-') {
                    return $err;
                }

                // Adjacent BASE64 sections
                if ($u7len > 2 && $str[$i+1] == '&' && $str[$i+2] != '-') {
                    return $err;
                }
            }
            // Not printable US-ASCII
            else if (ord($u7) < 0x20 || ord($u7) >= 0x7f) {
                return $err;
            }
            else {
                $p .= $u7;
            }
        }

        return $p;
    }
    /**
     * Converts string from standard UTF-7 (RFC 2152) to UTF-8.
     *
     * @param  string  Input string (UTF-7)
     *
     * @return string  Converted string (UTF-8)
     */
    public static function utf7_to_utf8($str){
        $Index_64 = array(
            0,0,0,0, 0,0,0,0, 0,0,0,0, 0,0,0,0,
            0,0,0,0, 0,0,0,0, 0,0,0,0, 0,0,0,0,
            0,0,0,0, 0,0,0,0, 0,0,0,1, 0,0,0,0,
            1,1,1,1, 1,1,1,1, 1,1,0,0, 0,0,0,0,
            0,1,1,1, 1,1,1,1, 1,1,1,1, 1,1,1,1,
            1,1,1,1, 1,1,1,1, 1,1,1,0, 0,0,0,0,
            0,1,1,1, 1,1,1,1, 1,1,1,1, 1,1,1,1,
            1,1,1,1, 1,1,1,1, 1,1,1,0, 0,0,0,0,
        );

        $u7len = strlen($str);
        $str   = strval($str);
        $res   = '';

        for ($i=0; $u7len > 0; $i++, $u7len--) {
            $u7 = $str[$i];
            if ($u7 == '+') {
                $i++;
                $u7len--;
                $ch = '';

                for (; $u7len > 0; $i++, $u7len--) {
                    $u7 = $str[$i];

                    if (!$Index_64[ord($u7)]) {
                        break;
                    }

                    $ch .= $u7;
                }

                if ($ch == '') {
                    if ($u7 == '-') {
                        $res .= '+';
                    }

                    continue;
                }

                $res .= self::utf16_to_utf8(base64_decode($ch));
            }
            else {
                $res .= $u7;
            }
        }

        return $res;
    }
    
    /**
     * Convert the data ($str) from UTF-8 to RFC 2060's UTF-7.
     * Unicode characters above U+FFFF are replaced by U+FFFE.
     * If input data is invalid, return an empty string.
     *
     * Translated from C to PHP by Thomas Bruederli <roundcube@gmail.com>
     *
     * @param string $str Input string (UTF-8)
     *
     * @return string Output string (UTF7-IMAP)
     */
    public static function utf8_to_utf7imap($str){
        $B64Chars = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7',
            '8', '9', '+', ','
        );

        $u8len  = strlen($str);
        $base64 = 0;
        $i      = 0;
        $p      = '';
        $err    = '';

        while ($u8len) {
            $u8 = $str[$i];
            $c  = ord($u8);

            if ($c < 0x80) {
                $ch = $c;
                $n  = 0;
            }
            else if ($c < 0xc2) {
                return $err;
            }
            else if ($c < 0xe0) {
                $ch = $c & 0x1f;
                $n  = 1;
            }
            else if ($c < 0xf0) {
                $ch = $c & 0x0f;
                $n  = 2;
            }
            else if ($c < 0xf8) {
                $ch = $c & 0x07;
                $n  = 3;
            }
            else if ($c < 0xfc) {
                $ch = $c & 0x03;
                $n  = 4;
            }
            else if ($c < 0xfe) {
                $ch = $c & 0x01;
                $n  = 5;
            }
            else {
                return $err;
            }

            $i++;
            $u8len--;

            if ($n > $u8len) {
                return $err;
            }

            for ($j=0; $j < $n; $j++) {
                $o = ord($str[$i+$j]);
                if (($o & 0xc0) != 0x80) {
                    return $err;
                }
                $ch = ($ch << 6) | ($o & 0x3f);
            }

            if ($n > 1 && !($ch >> ($n * 5 + 1))) {
                return $err;
            }

            $i += $n;
            $u8len -= $n;

            if ($ch < 0x20 || $ch >= 0x7f) {
                if (!$base64) {
                    $p .= '&';
                    $base64 = 1;
                    $b = 0;
                    $k = 10;
                }
                if ($ch & ~0xffff) {
                    $ch = 0xfffe;
                }

                $p .= $B64Chars[($b | $ch >> $k)];
                $k -= 6;
                for (; $k >= 0; $k -= 6) {
                    $p .= $B64Chars[(($ch >> $k) & 0x3f)];
                }

                $b = ($ch << (-$k)) & 0x3f;
                $k += 16;
            }
            else {
                if ($base64) {
                    if ($k > 10) {
                        $p .= $B64Chars[$b];
                    }
                    $p .= '-';
                    $base64 = 0;
                }

                $p .= chr($ch);
                if (chr($ch) == '&') {
                    $p .= '-';
                }
            }
        }

        if ($base64) {
            if ($k > 10) {
                $p .= $B64Chars[$b];
            }
            $p .= '-';
        }

        return $p;
    }
    
    /**
     * Removes non-unicode characters from input.
     *
     * @param mixed $input String or array.
     *
     * @return mixed String or array
     */
    public function clean($input){
        // handle input of type array
        if (is_array($input)) {
            foreach ($input as $idx => $val) {
                $input[$idx] = self::clean($val);
            }
            return $input;
        }

        if (!is_string($input) || $input == '') {
            return $input;
        }

        // iconv/mbstring are much faster (especially with long strings)
        if (function_exists('mb_convert_encoding')) {
            if (($res = mb_convert_encoding($input, 'UTF-8', 'UTF-8')) !== false) {
                return $res;
            }
        }

        if (function_exists('iconv')) {
            if (($res = @iconv('UTF-8', 'UTF-8//IGNORE', $input)) !== false) {
                return $res;
            }
        }

        $seq    = '';
        $out    = '';
        $regexp = '/^('.
//          '[\x00-\x7F]'.                                  // UTF8-1
            '|[\xC2-\xDF][\x80-\xBF]'.                      // UTF8-2
            '|\xE0[\xA0-\xBF][\x80-\xBF]'.                  // UTF8-3
            '|[\xE1-\xEC][\x80-\xBF][\x80-\xBF]'.           // UTF8-3
            '|\xED[\x80-\x9F][\x80-\xBF]'.                  // UTF8-3
            '|[\xEE-\xEF][\x80-\xBF][\x80-\xBF]'.           // UTF8-3
            '|\xF0[\x90-\xBF][\x80-\xBF][\x80-\xBF]'.       // UTF8-4
            '|[\xF1-\xF3][\x80-\xBF][\x80-\xBF][\x80-\xBF]'.// UTF8-4
            '|\xF4[\x80-\x8F][\x80-\xBF][\x80-\xBF]'.       // UTF8-4
            ')$/';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $chr = $input[$i];
            $ord = ord($chr);

            // 1-byte character
            if ($ord <= 0x7F) {
                if ($seq) {
                    $out .= preg_match($regexp, $seq) ? $seq : '';
                }
                $seq = '';
                $out .= $chr;
            // first (or second) byte of multibyte sequence
            }
            else if ($ord >= 0xC0) {
                if (strlen($seq) > 1) {
                    $out .= preg_match($regexp, $seq) ? $seq : '';
                    $seq = '';
                }
                else if ($seq && ord($seq) < 0xC0) {
                    $seq = '';
                }
                $seq .= $chr;
            // next byte of multibyte sequence
            }
            else if ($seq) {
                $seq .= $chr;
            }
        }

        if ($seq) {
            $out .= preg_match($regexp, $seq) ? $seq : '';
        }

        return $out;
    }
    
    public function convert($str, $from, $to = null){
        static $iconv_options   = null;
        static $mbstring_list   = null;
        static $mbstring_sch    = null;
        static $conv            = null;

        $to   = empty($to) ? "UTF-8" : $to;
        $from = self::parse_charset($from);

        // It is a common case when UTF-16 charset is used with US-ASCII content (#1488654)
        // In that case we can just skip the conversion (use UTF-8)
        if ($from == 'UTF-16' && !preg_match('/[^\x00-\x7F]/', $str)) {
            $from = 'UTF-8';
        }

        if ($from == $to || empty($str) || empty($from)) {
            return $str;
        }

        if ($iconv_options === null) {
            if (function_exists('iconv')) {
                // ignore characters not available in output charset
                $iconv_options = '//IGNORE';
                if (iconv('', $iconv_options, '') === false) {
                    // iconv implementation does not support options
                    $iconv_options = '';
                }
            }
        }

        // convert charset using iconv module
        if ($iconv_options !== null && $from != 'UTF7-IMAP' && $to != 'UTF7-IMAP') {
            // throw an exception if iconv reports an illegal character in input
            // it means that input string has been truncated
            set_error_handler(array('idmail_charset', 'error_handler'), E_NOTICE);
            try {
                $_iconv = iconv($from, $to . $iconv_options, $str);
            } catch (ErrorException $e) {
                $_iconv = false;
            }
            restore_error_handler();

            if ($_iconv !== false) {
                return $_iconv;
            }
        }

        if ($mbstring_list === null) {
            if (extension_loaded('mbstring')) {
                $mbstring_sch  = mb_substitute_character();
                $mbstring_list = mb_list_encodings();
                $mbstring_list = array_map('strtoupper', $mbstring_list);
            }
        }

        // convert charset using mbstring module
        if ($mbstring_list !== null) {
            $aliases['WINDOWS-1257'] = 'ISO-8859-13';
            // it happens that mbstring supports ASCII but not US-ASCII
            if (($from == 'US-ASCII' || $to == 'US-ASCII') && !in_array('US-ASCII', $mbstring_list)) {
                $aliases['US-ASCII'] = 'ASCII';
            }

            $mb_from = $aliases[$from] ? $aliases[$from] : $from;
            $mb_to   = $aliases[$to] ? $aliases[$to] : $to;

            // return if encoding found, string matches encoding and convert succeeded
            if (in_array($mb_from, $mbstring_list) && in_array($mb_to, $mbstring_list)) {
                if (mb_check_encoding($str, $mb_from)) {
                    // Do the same as //IGNORE with iconv
                    mb_substitute_character('none');
                    $out = mb_convert_encoding($str, $mb_to, $mb_from);
                    mb_substitute_character($mbstring_sch);

                    if ($out !== false) {
                        return $out;
                    }
                }
            }
        }

        // convert charset using bundled classes/functions
        if ($to == 'UTF-8') {
            if ($from == 'UTF7-IMAP') {
                if ($_str = self::utf7imap_to_utf8($str)) {
                    return $_str;
                }
            }
            else if ($from == 'UTF-7') {
                if ($_str = self::utf7_to_utf8($str)) {
                    return $_str;
                }
            }
            else if ($from == 'ISO-8859-1' && function_exists('utf8_encode')) {
                return utf8_encode($str);
            }
            else if (class_exists('utf8')) {
                if (!$conv) {
                    $conv = new utf8($from);
                }
                else {
                    $conv->loadCharset($from);
                }

                if ($_str = $conv->strToUtf8($str)) {
                    return $_str;
                }
            }
        }

        // encode string for output
        if ($from == 'UTF-8') {
            // @TODO: we need a function for UTF-7 (RFC2152) conversion
            if ($to == 'UTF7-IMAP' || $to == 'UTF-7') {
                if ($_str = self::utf8_to_utf7imap($str)) {
                    return $_str;
                }
            }
            else if ($to == 'ISO-8859-1' && function_exists('utf8_decode')) {
                return utf8_decode($str);
            }
            else if (class_exists('utf8')) {
                if (!$conv) {
                    $conv = new utf8($to);
                }
                else {
                    $conv->loadCharset($from);
                }

                if ($_str = $conv->strToUtf8($str)) {
                    return $_str;
                }
            }
        }

        // return original string
        return $str;
    }
}