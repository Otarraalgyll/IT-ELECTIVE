<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$_SESSION['cart'] ??= [];
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(int $v): string { return '₱'.number_format($v/100, 2); }
function redirect(string $page): never { header('Location: /?page='.$page); exit; }
$products = [
 1=>['name'=>'Everyday tote','category'=>'Accessories','price'=>79000,'icon'=>'👜','color'=>'#e9dfcf','description'=>'A roomy cotton companion for market mornings and everyday errands.'],
 2=>['name'=>'Stoneware mug','category'=>'Home','price'=>45000,'icon'=>'☕','color'=>'#e6e9df','description'=>'A comforting shape and a soft matte finish for your daily coffee ritual.'],
 3=>['name'=>'Linen cushion','category'=>'Home','price'=>89000,'icon'=>'🛋️','color'=>'#eadbd1','description'=>'Soft texture, warm color, and an easy refresh for your favorite corner.'],
 4=>['name'=>'Desk notebook','category'=>'Stationery','price'=>32000,'icon'=>'📓','color'=>'#dde4ea','description'=>'Make room for big ideas and small observations. 160 lined pages.'],
 5=>['name'=>'Amber candle','category'=>'Home','price'=>65000,'icon'=>'🕯️','color'=>'#eee3cc','description'=>'Warm amber and gentle wood notes for slower evenings at home.'],
 6=>['name'=>'Daily bottle','category'=>'Accessories','price'=>99000,'icon'=>'🧴','color'=>'#dce7dd','description'=>'A reusable insulated bottle to take along wherever your day leads.'],
];
$dir = __DIR__.'/storage';
if (!is_dir($dir)) mkdir($dir, 0700, true);
$db = new PDO('sqlite:'.$dir.'/shop.sqlite', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA busy_timeout = 5000');
$db->exec('CREATE TABLE IF NOT EXISTS orders (id TEXT PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL, address TEXT NOT NULL, items TEXT NOT NULL, total INTEGER NOT NULL, created_at TEXT NOT NULL)');
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'shop';
$error = '';
$fields = ['name'=>'','email'=>'','address'=>''];
function totals(array $products): array {
 $subtotal=0; foreach ($_SESSION['cart'] as $id=>$qty) $subtotal += $products[$id]['price']*$qty;
 $shipping=$subtotal > 0 && $subtotal < 200000 ? 12000 : 0;
 return [$subtotal,$shipping,$subtotal+$shipping];
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { http_response_code(403); exit('Invalid form token. Reload the page and try again.'); }
 $action=$_POST['action'] ?? '';
 $id=filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
 if (in_array($action, ['add', 'buy'], true) && isset($products[$id])) {
  $_SESSION['cart'][$id]=min(20,($_SESSION['cart'][$id] ?? 0)+1);
  if ($action==='buy') redirect('checkout');
  $_SESSION['flash']=$products[$id]['name'].' added to your bag.';
  redirect('shop');
 }
 if ($action==='update' && isset($products[$id])) {
  $qty=filter_var($_POST['quantity'] ?? null,FILTER_VALIDATE_INT);
  if ($qty !== false && $qty !== null && $qty >= 0 && $qty <=20) { if ($qty===0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id]=$qty; }
  redirect('cart');
 }
 if ($action==='order') {
  $page='checkout';
  foreach ($fields as $key=>$_) $fields[$key]=is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
  if (!$_SESSION['cart']) $error='Your bag is empty. Add an item before checking out.';
  elseif (strlen($fields['name'])<2 || strlen($fields['name'])>100 || !filter_var($fields['email'],FILTER_VALIDATE_EMAIL) || strlen($fields['email'])>254 || strlen($fields['address'])<10 || strlen($fields['address'])>1000) $error='Please enter a name, valid email, and complete delivery address.';
  else {
   $orderId=strtoupper(bin2hex(random_bytes(8)));
   [$subtotal,$shipping,$total]=totals($products);
   $items=[]; foreach ($_SESSION['cart'] as $pid=>$qty) $items[]=['name'=>$products[$pid]['name'],'price'=>$products[$pid]['price'],'quantity'=>$qty];
   try {
    $db->prepare('INSERT INTO orders VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$orderId,$fields['name'],$fields['email'],$fields['address'],json_encode($items,JSON_THROW_ON_ERROR),$total,gmdate('c')]);
    $_SESSION['receipt']=['id'=>$orderId,'total'=>$total]; $_SESSION['cart']=[]; $_SESSION['csrf']=bin2hex(random_bytes(32)); redirect('success');
   } catch (PDOException $ex) { error_log($ex->getMessage()); $error='Your order could not be saved. Please try again.'; }
  }
 }
}
[$subtotal,$shipping,$total]=totals($products);
$count=array_sum($_SESSION['cart']);
