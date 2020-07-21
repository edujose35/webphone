<?php

namespace App\Http\Controllers;

use App\Jobs\AsteriskAdminJob;
use Illuminate\Support\Facades\Queue;

class IndexController extends Controller
{
    const DIR_CONF = '/etc/asterisk/';
    const ALLOWED_FILES = [
        'pjsip' => 'pjsip.conf',
        'extensions' => 'extensions.conf'
    ];

    /**
     * Push asterisk job to run
     */
    public function index()
    {
//        Queue::push(new AsteriskAdminJob());


//        return dd($this->getPjsip());
//        return dd($this->getExtension());

        return $this->createEndpoint(
            '1003',
            '1003',
            '1003',
            'transport-udp',
            'ramais');
    }

    /**
     * @param $fileName string
     * @param bool $extension
     * @return array | null | string
     */
    private function getObjects($fileName, $extension = false)
    {
        if (!in_array($fileName, self::ALLOWED_FILES)) {
            return false;
        }

        $path = self::DIR_CONF . $fileName;
        $delimiter = $extension ? "/[^0-9a-zA-Z]*/" : "/[^0-9a-zA-Z\(\)\!]*/";

        if (file_exists($path)) {

            if (pathinfo($path)['extension'] === 'conf') {

                if (is_writable($path)) {/* Essa parte poderia ser executada sem as verificações anteriores */
                    $file = file($path);
                    $field = null;
                    $parsed = [];

                    foreach ($file as $line => $data) {
                        $data = trim($data);

                        if ($data) {
                            if ($data[0] == "[") {
                                $field = preg_replace($delimiter, "", $data);
                                $parsed[$field] = null;
                                continue;
                            }

                            if ($data[0] != ";") {
                                $params = preg_split("/(=>)|(=)/", $data);
                                $name = $extension ? $line . '-' . $params[0] : $params[0];
                                $parsed[$field][$name] = $params[1];
                            }
                        }

                    }

                    return $parsed;
                }

                return "Sem permissões para abrir o arquivo";
            }

            return "Arquivo não suportado";
        }

        return "Arquivo não existe";
    }

    public function getPjsip()
    {
        return $this->getObjects(self::ALLOWED_FILES['pjsip']);
    }

    public function getExtension()
    {
        return $this->getObjects(self::ALLOWED_FILES['extensions'], true);
    }

    /**
     * @param $username string
     * @param $password string
     * @param $transport string
     * @param $context string
     */
    private function createEndpoint($name, $username, $password, $transport, $context){
        $endpoint = [
            'type' => 'endpoint',
            'disallow' => 'all',
            'allow' => 'ulaw,alaw,gsm,g729',
            'send_rpid' => 'yes',
            'send_pai' => 'yes',
            'identify_by' => 'username',
            'transport' => $transport,
            'context' => $context,
            'callerid' => $username,
            'contact_user' => $username,
            'auth' => $username,
            'aors' => $username
        ];

        $auth = [
            'type' => 'auth',
            'auth_type' => 'userpass',
            'username' => $username,
            'password' => $password
        ];

        $aor = [
            'type' => 'aor',
            'max_contact' => 10,
            'minimum_expiration' => 600
        ];

        $data = "[$name]\n";
        foreach ($endpoint as $field => $value){
            $data .= "$field=$value\n";
        }

        $data .= "\n[$name]\n";
        foreach ($auth as $field => $value){
            $data .= "$field=$value\n";
        }

        $data .= "\n[$name]\n";
        foreach ($aor as $field => $value){
            $data .= "$field=$value\n";
        }

        //Validar se o objeto sendo criado já não existe
//        file_put_contents(self::DIR_CONF . self::ALLOWED_FILES['pjsip'], $data, FILE_APPEND);


        return dd($this->getPjsip());
    }
}
