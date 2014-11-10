<?php

class ebola {

    private $table = 'ebolaplay_hf';
    private $title = '';
    private $page = 'start';
    private $authed = false;
    private $user = '';
    private $udata = array('name' => '[Nem létező felhasználó]', 'email' => '[Nem létező e-mail cím]', 'tralala' => '[Nem létező tralala]');
    private $root = 'ebolaplay';
    private $db = false;

    public function __construct() {
        $this->load();
    }

    /**
     * Init
     *
     * Load PDO, needed variables, authenticates user and loads current page
     */
    private function load() {

        $this->db = new db();

        //get variables, page
        $this->setpage();

        //check for login
        $this->auth();

        //call desired page
        if ($this->authed) {
            switch ($this->page) {

                case 'logout':
                    $this->logout();
                    break;
                case 'email':
                    $this->display_mail();
                    break;
                case 'tralala':
                    $this->display_tralala();
                    break;

                case 'name':
                default:
                    $this->display_name();
                    break;
            }
        } else if ($this->page == 'reg') {

            $this->display_reg();
        } else {
            $this->display_login();
        }
    }

    /**
     * Set page id for methods to inspect
     */
    private function setpage() {

        $uri_array = array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));

        //extract page & set
        if (count($uri_array) > 1) {
            $this->page = $uri_array[count($uri_array) - 1];
        }
    }

    /**
     * Logout page
     *
     * Automatically redirects to main page
     */
    private function logout() {

        setcookie('login', '');
        $this->prg();
        //$this->setbody('You are logged out');
    }

    /**
     * Auth user
     *
     * Method checks for $_POST (from login form) and $_COOKIE for 'login' to check if user logged in
     */
    private function auth() {

        //check for cookie
        //if empty, show form
        //if not, show desired page
        //process $_POST, PRG!

        if (!empty($_POST['login'])) {

            //check db
            //check pw
            //allow
            try {

                $sth = $this->db->prepare("SELECT * FROM `" . $this->table . "` WHERE `email` = '" . $_POST['login']['email'] . "' LIMIT 1");
                $sth->setFetchMode(PDO::FETCH_ASSOC);
                $sth->execute();
                $data = $sth->fetch();

                if ($data && (md5($_POST['login']['password']) == $data['password'])) {

                    //allow
                    setcookie('login', $data['email']);
                } 
            } catch (PDOException $e) {

                //wrong!
                echo $e->getMessage();
            }
            $this->prg();
        }

        if (empty($_COOKIE['login'])) {

            //show form
            $this->authed = false;
        } else {

            $this->authed = true;
            $this->user = urldecode($_COOKIE['login']);
            //create an array for user data

            try {

                $udata = $this->db->prepare("SELECT `name`, `email`,`tralala` FROM `" . $this->table . "` WHERE `email` = '" . $this->user . "' LIMIT 1");
                $udata->setFetchMode(PDO::FETCH_ASSOC);
                $udata->execute();

                $this->udata = $udata->fetch();
            } catch (PDOException $e) {

                //stg wrong
                echo 'stg is wrong:' . $e->getMessage();
            }
        }
    }

    private function display_reg() {

        //check for post
        if (!empty($_POST['reg'])) {

            $ts = $_POST['reg'];

            $allow = true;

            if (empty($ts['email'])) {

                //e-mail missing
                $allow = false;
            }

            //check for existing mail
            try {

                $cm = $this->db->prepare("SELECT `email` FROM `" . $this->table . "` WHERE `email` = '" . $ts['email'] . "'");
                $cm->setFetchMode(PDO::FETCH_NUM);
                $cm->execute();
                $exists = $cm->fetch();

                if ($exists) {

                    $allow = false;
                }
            } catch (PDOException $e) {

                //a-a
            }
            //check for name
            if (empty($ts['name'])) {
                $allow = false;
            }

            if (empty($ts['tralala'])) {

                $allow = false;
            }

            //check for valid pass
            if (empty($ts['password']) || empty($ts['password2'])) {

                //password mismatch
                $allow = false;
            }

            //store
            if ($allow) {

                $store = $this->db->prepare("INSERT INTO `" . $this->table . "` (`name`, `email`, `password`, `tralala`) VALUES ('" .
                        $ts['name'] . "', '" . $ts['email'] . "', '" . md5($ts['password']) . "', '" . $ts['tralala'] . "')");
                $store->execute();
                $this->prg();
            }
            $this->prg('reg');
        }

        $this->settitle('Regisztráció');
        $this->setbody(file_get_contents('./html/reg_form.html'), false);
    }

    /**
     * Display login form
     */
    private function display_login() {

        $this->settitle('Bejelentkezés');

        $this->setbody(file_get_contents('./html/login_form.html'), false);
    }

    /**
     * Display full name
     */
    private function display_name() {

        //print_r($this->udata);

        $this->settitle('Név');
        $this->setbody($this->use_tpl(array('datatitle' => 'Név', 'data' => $this->udata['name'])));
    }

    /**
     * Display e-mail address
     */
    private function display_mail() {

        $this->settitle('E-mail');
        $this->setbody($this->use_tpl(array('datatitle' => 'E-mail cím', 'data' => $this->udata['email'])));
    }

    /**
     * Display favourite tralala
     */
    private function display_tralala() {


        $this->settitle('Kedvenc tralala');
        $this->setbody($this->use_tpl(array('datatitle' => 'Kedvenc tralala', 'data' => $this->udata['tralala'])));
    }

    /**
     * Template loader
     *
     * Load default template and replace data present in $data
     *
     * @param array $data Data to display
     * @return string Template HTML with replaced data fields
     */
    private function use_tpl(array $data) {

        $view = file_get_contents('./html/display_data.html');

        foreach ($data as $key => $val) {
            $view = str_replace('{@' . $key . '}', $val, $view);
        }

        return $view;
    }

    /**
     * Set page title
     *
     * Allow methods to set the page title
     *
     * @param string $title The title to set
     */
    private function settitle($title) {

        $this->title = $title;
    }

    /**
     * Set page body
     *
     * Allow methods to set the page content
     *
     * @param string $body HTML string to display
     * @param boolean $button If false, Do not display logout button. Default true.
     */
    private function setbody($body, $button = true) {

        $this->body = $body;
        if ($button) {

            $this->body .= file_get_contents('./html/logout_button.html');
        }
    }

    /**
     * Output page title
     * 
     */
    public function title() {

        if ($this->title) {
            echo $this->title . ' - ';
        }
    }

    /**
     * Output page navigation
     */
    public function nav() {

        //set active navbar
        $view = file_get_contents('./html/nav.html');

        $view = str_replace('{@' . $this->page . '}', ' class="active"', $view);
        $view = preg_replace('/\{@.*?\}/', '', $view);

        echo $view;
    }

    /**
     * Output page body
     */
    public function body() {

        echo $this->body;
    }

    /**
     * PHP Header redirection for PRG
     *
     * @param string $whereto The target
     */
    private function prg($whereto = '') {

        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/' . $this->root . (($whereto) ? '/' . $whereto : ''));
        die('Atirányítas');
    }

}
