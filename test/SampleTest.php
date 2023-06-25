
<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\WebDriverBy;

class SampleTest extends TestCase
{
    protected $pdo; // PDOオブジェクト用のプロパティ(メンバ変数)の宣言
    protected $driver;

    public function setUp(): void
    {
        // PDOオブジェクトを生成し、データベースに接続
        $dsn = "mysql:host=db;dbname=minishop;charset=utf8";
        $user = "mini";
        $password = "shop";
        try {
            $this->pdo = new PDO($dsn, $user, $password);
        } catch (Exception $e) {
            echo 'Error:' . $e->getMessage();
            die();
        }

        #XAMPP環境で実施している場合、$dsn設定を変更する必要がある
        //ファイルパス
        $rdfile = __DIR__ . '/../src/classes/dbdata.php';
        $val = "host=db;";

        //ファイルの内容を全て文字列に読み込む
        $str = file_get_contents($rdfile);
        //検索文字列に一致したすべての文字列を置換する
        $str = str_replace("host=localhost;", $val, $str);
        //文字列をファイルに書き込む
        file_put_contents($rdfile, $str);

        // chrome ドライバーの起動
        $host = 'http://172.17.0.1:4444/wd/hub'; #Github Actions上で実行可能なHost
        // chrome ドライバーの起動
        $this->driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());
    }

    public function testProductSelect()
    {
        // 指定URLへ遷移 (Google)
        $this->driver->get('http://php/src/index.php');

        // ラジオボタンをクリック
        $this->driver->findElement(WebDriverBy::xpath("//input[@type='radio' and @name='genre' and @value='music']"))->click();

        // inputタグの要素を取得
        $element_input = $this->driver->findElements(WebDriverBy::tagName('input'));

        // 画面遷移実行
        $element_input[3]->submit();

        // ジャンル別商品一覧画面のtdタグを取得
        $element_td = $this->driver->findElements(WebDriverBy::tagName('td'));

        //データベースの値を取得
        $sql = 'select * from items where genre = ?';       // SQL文の定義
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['music']);
        $items = $stmt->fetchAll();
        $i = 0;
        foreach ($items as $item) {
            $this->assertEquals($item['name'], $element_td[($i * 5) + 1]->getText(), 'ジャンル別商品一覧画面の処理に誤りがあります。');
            $i++;
        }
    }

    public function testProductDetail()
    {
        // 指定URLへ遷移 (Google)
        $this->driver->get('http://php/src/index.php');

        // ラジオボタンをクリック
        $this->driver->findElement(WebDriverBy::xpath("//input[@type='radio' and @name='genre' and @value='music']"))->click();

        // inputタグの要素を取得
        $element_input = $this->driver->findElements(WebDriverBy::tagName('input'));

        // 画面遷移実行
        $element_input[3]->submit();

        // リンクをクリック
        $element_a = $this->driver->findElements(WebDriverBy::tagName('a'));
        $element_a[0]->click();

        // tdタグの要素を取得
        $element_td = $this->driver->findElements(WebDriverBy::tagName('td'));

        //データベースの値を取得
        $sql = 'select * from items where ident = ?';       // SQL文の定義
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['11']);
        $item = $stmt->fetch();

        // assert
        $this->assertEquals($item['name'], $element_td[0]->getText(), '商品詳細画面の処理に誤りがあります。');

        $this->driver->close();
    }
}
