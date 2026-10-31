<?php
session_start();
require_once "database/db.php";

$userId = $_SESSION['user_id'] ?? null;
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function getLandmarks(mysqli $conn,array $ids): array {
    $ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($v)=>$v>0)));
    if(!$ids)return [];
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $types=str_repeat('i',count($ids));
    $stmt=$conn->prepare("SELECT ID,NAME,CATEGORY,DESCRIPTION,LATITUDE,LONGITUDE,
                          VISIT_DURATION AS duration
                          FROM madinah_landmarks WHERE ID IN ($marks)");
    $stmt->bind_param($types,...$ids);
    $stmt->execute();
    $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $indexed=[];
    foreach($rows as $r)$indexed[(int)$r['ID']]=$r;
    $ordered=[];
    foreach($ids as $id)if(isset($indexed[$id]))$ordered[]=$indexed[$id];
    return $ordered;
}

$error='';
$savedTrip=null;

// بدء مسار من معالم محددة (من الذكي أو اليدوي)
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='start'){
    $ids=json_decode($_POST['place_ids']??'[]',true);
    if(!is_array($ids))$ids=[];
    $ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($x)=>$x>0)));
    if(!$ids){$error='يرجى اختيار معلم واحد على الأقل.';}
    else{
        $_SESSION['current_trip']=[
            'trip_name'=>'المسار الحالي',
            'place_ids'=>$ids,
            'route_type'=>$_POST['route_type']??'manual',
            'start_lat'=>(float)($_POST['start_lat']??24.4672),
            'start_lng'=>(float)($_POST['start_lng']??39.6111),
            'source_trip_id'=>null
        ];
        header('Location: route_result.php?current=1');exit;
    }
}

// إنشاء نسخة مستقلة من مسار سابق مع إمكانية إضافة معالم
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='clone'){
    if(!$userId){$error='يرجى تسجيل الدخول أولًا.';}
    else{
        $tripId=(int)($_POST['trip_id']??0);
        $stmt=$conn->prepare("SELECT id,trip_name FROM trips WHERE id=? AND user_id=?");
        $stmt->bind_param('ii',$tripId,$userId);$stmt->execute();
        $trip=$stmt->get_result()->fetch_assoc();$stmt->close();
        if(!$trip){$error='المسار غير موجود أو لا تملك صلاحية الوصول إليه.';}
        else{
            $stmt=$conn->prepare("SELECT landmark_id FROM trip_places WHERE trip_id=? ORDER BY visit_order");
            $stmt->bind_param('i',$tripId);$stmt->execute();
            $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
            $ids=array_map('intval',array_column($rows,'landmark_id'));
            $_SESSION['current_trip']=[
                'trip_name'=>$trip['trip_name'].' - نسخة جديدة',
                'place_ids'=>$ids,
                'route_type'=>'reused',
                'start_lat'=>24.4672,'start_lng'=>39.6111,
                'source_trip_id'=>$tripId
            ];
            header('Location: route_result.php?current=1');exit;
        }
    }
}

// تحميل تفاصيل المسار السابق مع التحقق من الملكية
if(isset($_GET['trip_id'])){
    if(!$userId){http_response_code(401);exit('يرجى تسجيل الدخول.');}
    $tripId=(int)$_GET['trip_id'];
    $stmt=$conn->prepare("SELECT id,trip_name,start_location,total_duration
                          FROM trips WHERE id=? AND user_id=?");
    $stmt->bind_param('ii',$tripId,$userId);$stmt->execute();
    $savedTrip=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$savedTrip){http_response_code(404);exit('المسار غير موجود.');}
    $stmt=$conn->prepare("SELECT landmark_id FROM trip_places WHERE trip_id=? ORDER BY visit_order");
    $stmt->bind_param('i',$tripId);$stmt->execute();
    $savedRows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
    $ids=array_map('intval',array_column($savedRows,'landmark_id'));
    $places=getLandmarks($conn,$ids);
}
// عرض المسار الحالي الموجود في الجلسة
elseif(isset($_GET['current']) && isset($_SESSION['current_trip'])){
    $current=$_SESSION['current_trip'];
    $ids=$current['place_ids']??[];
    $places=getLandmarks($conn,$ids);
    $savedTrip=['id'=>null,'trip_name'=>$current['trip_name']??'المسار الحالي',
                'start_location'=>'نقطة البداية','total_duration'=>null];
}else{
    header('Location: visit_planner.php');exit;
}

$allRes=$conn->query("SELECT ID,NAME,CATEGORY FROM madinah_landmarks ORDER BY NAME");
$allLandmarks=$allRes?$allRes->fetch_all(MYSQLI_ASSOC):[];
$placeJson=json_encode($places,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$isCurrent=isset($_GET['current']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($savedTrip['trip_name'])?> | أثر المدينة</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#24332c;font-family:Tahoma,Arial,sans-serif}
main{max-width:1000px;margin:auto;padding:20px 15px 45px}h1{color:#26734f;font-size:25px}
.panel{background:white;border:1px solid #e0e8e2;border-radius:14px;padding:16px;margin:15px 0}
#map{height:360px;border-radius:12px;z-index:1}.place{padding:12px 0;border-bottom:1px solid #edf0ee}
.place:last-child{border:0}.muted{color:#718078;font-size:13px}
.btn{font:inherit;border:0;border-radius:9px;padding:12px 16px;cursor:pointer;background:#26734f;color:white}
.btn.red{background:#b83d3d}.btn.light{background:#e8f1eb;color:#26734f}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
input,select{font:inherit;padding:10px;border:1px solid #d9e2dc;border-radius:9px}
.add-list{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;max-height:250px;overflow:auto}
.add-item{border:1px solid #e2e9e4;padding:10px;border-radius:9px;display:flex;gap:8px}
@media(max-width:600px){#map{height:300px}.add-list{grid-template-columns:1fr}}
</style>
</head>
<body><main>
<a href="visit_planner.php" class="btn light">← الصفحة الرئيسية</a>
<h1><?=e($savedTrip['trip_name'])?></h1>
<?php if($error): ?><div class="panel"><?=e($error)?></div><?php endif; ?>

<div class="panel">
    <h2>خريطة المسار</h2>
    <div id="map"></div>
</div>

<div class="panel">
    <h2>ترتيب المعالم</h2>
    <div id="placeList">
    <?php foreach($places as $i=>$p): ?>
        <div class="place">
            <strong><?=($i+1)?>. <?=e($p['NAME'])?></strong>
            <div class="muted"><?=e($p['CATEGORY']??'')?></div>
            <?php if(!empty($p['DESCRIPTION'])): ?><p class="muted"><?=e($p['DESCRIPTION'])?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php if(!$places): ?><p class="muted">لا توجد معالم في هذا المسار.</p><?php endif; ?>
</div>

<?php if(!$isCurrent): ?>
<div class="panel">
    <h2>إدارة المسار السابق</h2>
    <p class="muted">يمكنك إنشاء نسخة جديدة من هذا المسار أو حذفه نهائيًا.</p>
    <div class="actions">
        <form method="post">
            <input type="hidden" name="action" value="clone">
            <input type="hidden" name="trip_id" value="<?=(int)$savedTrip['id']?>">
            <button class="btn" type="submit">إنشاء كمسار حالي جديد</button>
        </form>
        <form method="post" action="save_trip.php" onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار نهائيًا؟');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="trip_id" value="<?=(int)$savedTrip['id']?>">
            <button class="btn red" type="submit">حذف المسار</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="panel">
    <h2>إضافة معالم إلى المسار الحالي (اختياري)</h2>
    <p class="muted">يمكنك إبقاء المسار كما هو أو تحديد معالم إضافية.</p>
    <input id="addSearch" placeholder="ابحث عن معلم...">
    <div id="addList" class="add-list" style="margin-top:12px"></div>
    <div class="actions">
        <button class="btn light" onclick="applyAdditions()">إضافة المعالم المحددة</button>
        <button class="btn" onclick="saveTrip()">حفظ المسار</button>
    </div>
    <p id="status" class="muted"></p>
</div>
<?php endif; ?>
</main>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const places=<?= $placeJson ?: '[]' ?>;
const isCurrent=<?= $isCurrent?'true':'false' ?>;
const allLandmarks=<?=json_encode($allLandmarks,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
let currentIds=places.map(p=>String(p.ID));
const map=L.map('map').setView([24.4672,39.6111],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
 attribution:'&copy; OpenStreetMap'
}).addTo(map);
const coords=[];
places.forEach((p,i)=>{
 const lat=Number(p.LATITUDE),lng=Number(p.LONGITUDE);
 if(Number.isFinite(lat)&&Number.isFinite(lng)){
  coords.push([lat,lng]);
  L.marker([lat,lng]).addTo(map).bindPopup(`${i+1}. ${p.NAME}`);
 }
});
if(coords.length)map.fitBounds(coords,{padding:[25,25]});
if(isCurrent){
 const addList=document.getElementById('addList');
 function renderAdd(){
  const q=document.getElementById('addSearch').value.toLowerCase().trim();
  addList.replaceChildren();
  allLandmarks.filter(p=>!currentIds.includes(String(p.ID))&&String(p.NAME).toLowerCase().includes(q))
  .forEach(p=>{
   const label=document.createElement('label');label.className='add-item';
   const cb=document.createElement('input');cb.type='checkbox';cb.value=p.ID;
   const name=document.createElement('span');name.textContent=p.NAME;
   label.append(cb,name);addList.append(label);
  });
 }
 document.getElementById('addSearch').addEventListener('input',renderAdd);
 renderAdd();

 function applyAdditions(){
  const chosen=[...addList.querySelectorAll('input:checked')].map(x=>x.value);
  if(!chosen.length){document.getElementById('status').textContent='لم تختر معالم إضافية.';return;}
  currentIds=[...new Set([...currentIds,...chosen])];
  const form=document.createElement('form');form.method='post';form.action='route_result.php';
  const fields={action:'start',place_ids:JSON.stringify(currentIds),route_type:'reused'};
  for(const [k,v] of Object.entries(fields)){const i=document.createElement('input');i.type='hidden';i.name=k;i.value=v;form.append(i);}
  document.body.append(form);form.submit();
 }
 window.applyAdditions=applyAdditions;
 window.saveTrip=async function(){
  const status=document.getElementById('status');status.textContent='جارٍ حفظ المسار...';
  const response=await fetch('save_trip.php',{method:'POST',
   headers:{'Content-Type':'application/json'},
   body:JSON.stringify({action:'save',trip_name:<?=json_encode($savedTrip['trip_name'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>,
    place_ids:currentIds})
  });
  const data=await response.json();
  if(data.success){location.href='visit_planner.php';}
  else status.textContent=data.message||'تعذر حفظ المسار.';
 };
}
</script>
<?php include __DIR__ . "/translate_widget.php"; ?>
</body></html>