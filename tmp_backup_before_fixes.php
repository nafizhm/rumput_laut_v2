<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();
if($db->getDriverName()!=='mysql'||$db->getDatabaseName()!=='rumput_laut_v2') throw new RuntimeException('Unexpected live database');
$backup=['database'=>$db->getDatabaseName(),'created_at'=>date(DATE_ATOM),'tables'=>[]];
foreach($db->getSchemaBuilder()->getTables($db->getDatabaseName()) as $table){
 $name=$table['name'];
 $create=(array)$db->selectOne('SHOW CREATE TABLE `'.str_replace('`','``',$name).'`');
 $backup['tables'][$name]=['schema'=>array_values($create)[1],'rows'=>$db->table($name)->get()->map(fn($r)=>(array)$r)->all()];
}
$dir=storage_path('app/private/backups'); if(!is_dir($dir)) mkdir($dir,0700,true);
$path=$dir.'/database-before-production-fixes-'.date('Ymd-His').'.json';
$json=json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
if(file_put_contents($path,$json,LOCK_EX)!==strlen($json)||hash('sha256',file_get_contents($path))!==hash('sha256',$json)) throw new RuntimeException('Backup failed');
echo 'Verified backup: '.$path.PHP_EOL;
