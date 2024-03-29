<?php
session_start();
// $mysql_link = mysql_connect('datsko.mysql.ukraine.com.ua', 'datsko_todaynews', 'rvbef8vy');
define('ENV', $_SERVER['HTTP_HOST'] == 'infa.loc.ua' ? 'LOC' : 'LIVE');
require_once('./php/transliteration/JTransliteration.php');
require_once('./php/db.php');

$act = isset($_POST['act']) ? $_POST['act'] : $_GET['act'];
$act2 = isset($_POST['act2']) ? $_POST['act2'] : $_GET['act2'];
$act3 = isset($_POST['act3']) ? $_POST['act3'] : $_GET['act3'];

$data = json_decode(base64_decode($_REQUEST['data']));

if ((
    $act == 'getCategoriesAdmin'
    || $act == 'delete'
    || $act == 'edit'
    || $act == 'addArticle'
    || $act == 'getArticlesAdmin'
  ) && (
    !isset($_SESSION['user_id'])
    || !is_numeric($_SESSION['user_id'])
    || $_SESSION['user_id'] <= 0
  )) {
  echo json_encode(array('error' => 'login'));
  exit();
}

function GUI($length = 32) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

switch($act) {
  case 'runLogin':
    $session_id = GUI(40);
    $start = rand(1, 24);
    $end = 24 - $start;
    $start = GUI($start);
    $end = GUI($end);

    $result = q("SELECT id, email FROM users WHERE email = '" . $data->email . "' AND password = '" . $data->password . "'");

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      $result = q("INSERT INTO sessions (
          code,
          code_start,
          code_end,
          os,
          browser,
          ip,
          ip2,
          ip3,
          datecreated,
          user
        ) VALUES (
          '" . $session_id . "',
          '" . $start . "',
          '" . $end . "',
          '" . php_uname('a') . "',
          '" . $_SERVER['HTTP_USER_AGENT'] . "',
          '" . $_SERVER['REMOTE_ADDR'] . "',
          '" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "',
          '" . $_SERVER['HTTP_CLIENT_IP'] . "',
          '" . date('Y-m-d H:i:s') . "',
          '" . $user['id'] . "'
        )");
      
      // echo json_encode([$result, 123, $data, $mysqli->insert_id]);
      $_SESSION['user_id'] = $user['id'];
      echo json_encode(['code' => $start . $session_id . $end, 'user' => $user['email']]);

      // echo true;
    } else {
      echo json_encode(['error' => 'Error']);
    }
    

    
    break;

  case 'runSignup':
    $result = q("INSERT INTO users (email) VALUES ('" . $data->title . "')");
    
    echo json_encode([$result, 123, $data, $mysqli->insert_id]);
    break;

  case 'getCategories':
    $result = q("SELECT * FROM categories WHERE state = 1 AND id <> 13 ORDER BY title ASC");
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'url' => $row['url'],
          'subid' => $row['subid']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getCategoriesAdmin':
    $result = q("SELECT * FROM categories WHERE state = 1 ORDER BY title ASC");
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'url' => $row['url'],
          'subid' => $row['subid']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getBannersAdmin':
    $result = q("SELECT b.*, c.title AS category_title
      FROM banners AS b
      LEFT JOIN categories AS c ON c.id = b.category
      ORDER BY b.date_end DESC");
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'image' => $row['image'],
          'clicks' => $row['clicks'],
          'views' => $row['views'],
          'link' => $row['link'],
          'client' => $row['client'],
          'date_start' => date('Y-m-d', $row['date_start']),
          'date_end' => date('Y-m-d', $row['date_end']),
          'category' => $row['category'],
          'category_title' => $row['category_title'],
          'position' => $row['position']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getBanners':
    $result = q("SELECT b.*, c.url AS category_url
      FROM banners AS b
      LEFT JOIN categories AS c ON c.id = b.category
      ORDER BY b.date_end DESC");
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'image' => $row['image'],
          'clicks' => $row['clicks'],
          'views' => $row['views'],
          'link' => $row['link'],
          'client' => $row['client'],
          'date_start' => date('Y-m-d', $row['date_start']),
          'date_end' => date('Y-m-d', $row['date_end']),
          'category' => $row['category'],
          'category_url' => $row['category_url'],
          'position' => $row['position']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getArticles':
    $result = q("SELECT c.* FROM categories AS c WHERE c.url = '" . $data->category_url . "'");
    $category = $result->fetch_assoc();

    if ($category['id'] > 0) {
      $result = q("SELECT a.* FROM articles AS a
        WHERE a.category = '" . $category['id'] . "' AND a.state = 1
        ORDER BY a.datecreated DESC");
      
      $articles = array();
      while ($article = $result->fetch_assoc()) {
        array_push($articles, array(
            'id' => $article['id'],
            'title' => $article['title'],
            'content' => $article['content'],
            'state' => $article['state'],
            'url' => $article['url'],
            'category_title' => $category['title'],
            'category_url' => $category['url'],
            'date' => date('d.m.Y', $article['datecreated']),
            'time' => date('H:i', $article['datecreated']),
            'subid' => $article['subid']
          )
        );
      }
      echo json_encode(['category' => $category, 'articles' => $articles]);
    } else {
      echo json_encode(['error' => 'Not_found']);
    }
    break;

  case 'getArticlesHomeMostViewed':
    $result = q("SELECT a.*, c.title AS category_title, c.url AS category_url FROM articles AS a
      INNER JOIN categories AS c ON c.id = a.category AND c.id <> 13
      WHERE a.state = 1
      ORDER BY a.views DESC
      LIMIT 11");
    // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'content' => $row['content'],
          'state' => $row['state'],
          'url' => $row['url'],
          'category_title' => $row['category_title'],
          'category_url' => $row['category_url'],
          'date' => date('d.m.Y', $row['datecreated']),
          'time' => date('H:i', $row['datecreated']),
          'subid' => $row['subid'],
          'views' => $row['views']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getArticlesHomeMostNew':
    $result = q("SELECT a.*, c.title AS category_title, c.url AS category_url FROM articles AS a
      INNER JOIN categories AS c ON c.id = a.category AND c.id <> 13
      WHERE a.state = 1
      ORDER BY a.datecreated DESC
      LIMIT 5");
    // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'content' => $row['content'],
          'state' => $row['state'],
          'url' => $row['url'],
          'category_title' => $row['category_title'],
          'category_url' => $row['category_url'],
          'date' => date('d.m.Y', $row['datecreated']),
          'time' => date('H:i', $row['datecreated']),
          'subid' => $row['subid'],
          'views' => $row['views']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getArticlesMostViewed':
    $result = q("SELECT c.* FROM categories AS c WHERE c.url = '" . $data->category_url . "'");
    $category = $result->fetch_assoc();

    if ($category['id'] > 0) {
      $result = q("SELECT a.* FROM articles AS a
        WHERE a.category = '" . $category['id'] . "' AND a.state = 1
        ORDER BY a.views DESC
        LIMIT " . ($data->limit ? $data->limit : 5));
      // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
      
      $rows = array();
      while ($row = $result->fetch_assoc()) {
        array_push($rows, array(
            'id' => $row['id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'state' => $row['state'],
            'url' => $row['url'],
            'category_title' => $category['title'],
            'category_url' => $category['url'],
            'date' => date('d.m.Y', $row['datecreated']),
            'time' => date('H:i', $row['datecreated']),
            'subid' => $row['subid'],
            'views' => $row['views']
          )
        );
      }
      echo json_encode(['category' => $category, 'articles' => $rows]);
    } else {
      echo json_encode(['error' => 'Not_found']);
    }
    break;

  case 'getArticlesMostNew':
    $result = q("SELECT c.* FROM categories AS c WHERE c.url = '" . $data->category_url . "'");
    $category = $result->fetch_assoc();

    if ($category['id'] > 0) {
      $result = q("SELECT a.* FROM articles AS a
        WHERE a.category = '" . $category['id'] . "' AND a.state = 1
        ORDER BY a.datecreated DESC
        LIMIT " . ($data->limit ? $data->limit : 5));
      // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
      
      $rows = array();
      while ($row = $result->fetch_assoc()) {
        array_push($rows, array(
            'id' => $row['id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'state' => $row['state'],
            'url' => $row['url'],
            'category_title' => $category['title'],
            'category_url' => $category['url'],
            'date' => date('d.m.Y', $row['datecreated']),
            'time' => date('H:i', $row['datecreated']),
            'subid' => $row['subid'],
            'views' => $row['views']
          )
        );
      }
      echo json_encode(['category' => $category, 'articles' => $rows]);
    } else {
      echo json_encode(['error' => 'Not_found']);
    }
    break;

  case 'getArticlesAdmin':
    $result = q("SELECT
        a.*,
        IF(a.category = '0', 'no_category', (SELECT title FROM categories WHERE id = a.category)) AS category_name,
        IF(a.category = '0', '', (SELECT url FROM categories WHERE id = a.category)) AS category_url
      FROM articles AS a
      ORDER BY a.datecreated DESC");
    // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'url' => $row['url'],
          'category_url' => $row['category_url'],
          'category_name' => $row['category_name'],
          'date' => date('d.m.Y', $row['datecreated']),
          'time' => date('H:i', $row['datecreated']),
          'subid' => $row['subid'],
          'cost' => $row['cost'],
          'paid' => $row['paid'],
          'content' => $row['content']
        )
      );
    }
    echo json_encode($rows);
    break;

  case 'getArticle':
    $result = q("SELECT a.* FROM categories AS c
      INNER JOIN articles AS a ON a.category = c.id
      WHERE c.url = '" . $data->category . "' AND a.url = '" . $data->article . "'
      ORDER BY a.datecreated DESC");
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'url' => $row['url'],
          'date' => date('d.m.Y', $row['datecreated']),
          'content' => $row['content'],
          'views' => $row['views'] + 1
        )
      );
    }
    $result = q("UPDATE articles SET views = views + 1 WHERE id = " . $rows[0]['id']);
    echo json_encode($rows[0]);
    break;

  case 'getArticleSingle':
    $result = q("SELECT a.* FROM articles AS a
      WHERE a.url = '" . $data->article . "' AND a.state = 1");
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'url' => $row['url'],
          'date' => date('d.m.Y', $row['datecreated']),
          'content' => $row['content'],
          'views' => $row['views'] + 1
        )
      );
    }
    $result = q("UPDATE articles SET views = views + 1 WHERE id = " . $rows[0]['id']);
    echo json_encode($rows[0]);
    break;

  case 'addArticle':  
    if (isset($_REQUEST['part'])) {
      if (!isset($_SESSION['data'])) {
        $_SESSION['data'] = '';
      }
      $_SESSION['data'] .= $_REQUEST['part'];
    } else {
      if (isset($_REQUEST['finish'])) {
        $data = json_decode(base64_decode($_SESSION['data'] . $_REQUEST['finish']));
        $finish = true;
        $_SESSION['data'] = '';
      } else {
        $finish = false;
        $data = json_decode(base64_decode($_REQUEST['data']));
      }
      
      $url = strtolower(str_replace(' ', '-', preg_replace('!\s+!', ' ', preg_replace("/[^a-zA-Z0-9 ]+/", "", JTransliteration::transliterate(base64_decode($data->title))))));
      // echo $url;
      // exit();

      if ($data->id > 0) {
        $result = q("UPDATE articles SET
          title = '" . base64_decode($data->title) . "',
          content = '" . base64_decode($data->content) . "',
          category = '" . $data->category . "',
          datemodified = '" . strtotime(date('Y-m-d H:i:s')) . "',
          state = '" . $data->state . "'
          WHERE id = '" . $data->id . "'");
      } else {
        $result = q("INSERT INTO articles (
          title,
          content,
          category,
          url,
          datecreated,
          state
          ) VALUES (
          '" . base64_decode($data->title) . "',
          '" . base64_decode($data->content) . "',
          '" . $data->category . "',
          '" . $url . "',
          '" . strtotime(date('Y-m-d H:i:s')) . "',
          '" . $data->state . "'
          )");
      }
      

      
      
      // $rows = array();
      // while ($row = $result->fetch_assoc()) {
      //   array_push($rows, array(
      //       'id' => $row['id'],
      //       'title' => $row['title'],
      //       'state' => $row['state'],
      //       'url' => $row['url'],
      //       'subid' => $row['subid']
      //     )
      //   );
      // }
      echo json_encode([$result, $finish, $data, $mysqli->insert_id, $url, $_SESSION['data'], $_REQUEST['finish']]);
    }
    break;

  case 'addBanner':  
    if (isset($_REQUEST['part'])) {
      if (!isset($_SESSION['data'])) {
        $_SESSION['data'] = '';
      }
      $_SESSION['data'] .= $_REQUEST['part'];
    } else {
      if (isset($_REQUEST['finish'])) {
        $data = json_decode(base64_decode($_SESSION['data'] . $_REQUEST['finish']));
        $finish = true;
        $_SESSION['data'] = '';
      } else {
        $finish = false;
        $data = json_decode(base64_decode($_REQUEST['data']));
      }
      
      $url = strtolower(str_replace(' ', '-', preg_replace('!\s+!', ' ', preg_replace("/[^a-zA-Z0-9 ]+/", "", JTransliteration::transliterate(base64_decode($data->title))))));
      // echo $url;
      // exit();

      if ($data->id > 0) {
        $result = q("UPDATE banners SET
          title = '" . base64_decode($data->title) . "',
          image = '" . base64_decode($data->image) . "',
          link = '" . base64_decode($data->link) . "',
          date_modified = '" . strtotime(date('Y-m-d H:i:s')) . "',
          date_start = '" . strtotime(date(base64_decode($data->date_start))) . "',
          date_end = '" . strtotime(date(base64_decode($data->date_end))) . "',
          state = '" . $data->state . "',
          category = '" . $data->category . "',
          position = '" . $data->position . "'
          WHERE id = '" . $data->id . "'");
      } else {
        $result = q("INSERT INTO banners (
          title,
          image,
          link,
          date_created,
          date_start,
          date_end,
          state,
          category,
          position
          ) VALUES (
          '" . base64_decode($data->title) . "',
          '" . base64_decode($data->image) . "',
          '" . base64_decode($data->link) . "',
          '" . strtotime(date('Y-m-d H:i:s')) . "',
          '" . strtotime(date(base64_decode($data->date_start))) . "',
          '" . strtotime(date(base64_decode($data->date_end))) . "',
          '" . $data->state . "',
          '" . $data->category . "',
          '" . $data->position . "'
          )");
      }
      

      
      
      // $rows = array();
      // while ($row = $result->fetch_assoc()) {
      //   array_push($rows, array(
      //       'id' => $row['id'],
      //       'title' => $row['title'],
      //       'state' => $row['state'],
      //       'url' => $row['url'],
      //       'subid' => $row['subid']
      //     )
      //   );
      // }
      echo json_encode([$result, $finish, $data, $mysqli->insert_id, $url, $_SESSION['data'], $_REQUEST['finish']]);
    }
    break;

  case 'delete':
    switch ($data->type) {
      case 'article':
        $result = q("DELETE FROM articles WHERE id = '" . $data->id . "'");
        echo json_encode($result);
        break;

      case 'category':
        $result = q("DELETE FROM categories WHERE id = '" . $data->id . "'");
        echo json_encode($result);
        break;

      case 'user':
        $result = q("DELETE FROM users WHERE id = '" . $data->id . "'");
        echo json_encode($result);
        break;

      case 'banner':
        $result = q("DELETE FROM banners WHERE id = '" . $data->id . "'");
        echo json_encode($result);
        break;
    }
    break;

  case 'edit':
    switch ($data->type) {
      case 'article':
        $result = q("SELECT a.* FROM articles AS a
          WHERE a.id = '" . $data->id . "'");
        // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
        
        $rows = array();
        while ($row = $result->fetch_assoc()) {
          array_push($rows, array(
              'id' => $row['id'],
              'title' => base64_encode($row['title']),
              'state' => $row['state'],
              'url' => $row['url'],
              'category' => $row['category'],
              'content' => base64_encode($row['content'])
            )
          );
        }
        echo json_encode($rows[0]);
        break;

      case 'banner':
        $result = q("SELECT b.* FROM banners AS b
          WHERE b.id = '" . $data->id . "'");
        // $result = q("SELECT a.* FROM articles AS a WHERE a.category = 2 ORDER BY a.datecreated DESC");
        
        $rows = array();
        while ($row = $result->fetch_assoc()) {
          array_push($rows, array(
              'id' => $row['id'],
              'title' => base64_encode($row['title']),
              'image' => base64_encode($row['image']),
              'link' => base64_encode($row['link']),
              'date_modified' => base64_encode(date('Y-m-d H:i:s', $row['date_modified'])),
              'date_start' => base64_encode(date('Y-m-d H:i:s', $row['date_start'])),
              'date_end' => base64_encode(date('Y-m-d H:i:s', $row['date_end'])),
              'state' => $row['state'],
              'category' => $row['category'],
              'position' => $row['position']
            )
          );
        }
        echo json_encode($rows[0]);
        break;

      // case 'category':
      //   $result = q("DELETE FROM categories WHERE id = '" . $_REQUEST['id'] . "'");
      //   echo json_encode($result);
      //   break;

      // case 'user':
      //   $result = q("DELETE FROM users WHERE id = '" . $_REQUEST['id'] . "'");
      //   echo json_encode($result);
      //   break;
    }
    break;

  case 'addUser':
    $code = crypt($data->email . strtotime(date('Y-m-d H:i:s')) . 'code', $data->password);
    $refresh = crypt($code, $data->password);
    $result = q("INSERT INTO users (
      code,
      refresh,
      datecreated,
      email,
      password
      ) VALUES (
      '" . $code . "',
      '" . $refresh . "',
      '" . date('Y-m-d H:i:s') . "',
      '" . $data->email . "',
      '" . crypt($data->password, $data->password) . "'
      )");
    
    echo json_encode($code . '/' . $refresh);
    break;

  case 'addBannerView':
    $result = q("INSERT INTO banners_show (
        banner_id,
        category,
        os,
        browser,
        ip,
        ip2,
        ip3,
        date_created
      ) VALUES (
        '" . $data->id . "',
        '" . $data->category . "',
        '" . php_uname('a') . "',
        '" . $_SERVER['HTTP_USER_AGENT'] . "',
        '" . $_SERVER['REMOTE_ADDR'] . "',
        '" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "',
        '" . $_SERVER['HTTP_CLIENT_IP'] . "',
        '" . strtotime(date('Y-m-d H:i:s')) . "'
      )");

    $result = q("UPDATE banners SET views = views + 1 WHERE id = " . $data->id);
    
    echo json_encode($result);
    break;

  case 'addBannerClick':
    $result = q("INSERT INTO banners_click (
        banner_id,
        category,
        os,
        browser,
        ip,
        ip2,
        ip3,
        date_created
      ) VALUES (
        '" . $data->id . "',
        '" . $data->category . "',
        '" . php_uname('a') . "',
        '" . $_SERVER['HTTP_USER_AGENT'] . "',
        '" . $_SERVER['REMOTE_ADDR'] . "',
        '" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "',
        '" . $_SERVER['HTTP_CLIENT_IP'] . "',
        '" . strtotime(date('Y-m-d H:i:s')) . "'
      )");

    $result = q("UPDATE banners SET clicks = clicks + 1 WHERE id = " . $data->id);
    
    echo json_encode($result);
    break;

  case 'getUser':
    $result = q("SELECT * FROM articles WHERE state = 1 ORDER BY datecreated DESC");
    $rows = array();
    while ($row = $result->fetch_assoc()) {
      array_push($rows, array(
          'id' => $row['id'],
          'title' => $row['title'],
          'state' => $row['state'],
          'content' => $row['content'],
          'category' => $row['category'],
          'datecreated' => date('H:i d.m.y', strtotime($row['datecreated']))
        )
      );
    }
    echo json_encode($rows);
    break;

  default:
    $request = key($_REQUEST);
    $request = explode('/', $request);
    echo json_encode([$request, 321]);
    break;
}


?>