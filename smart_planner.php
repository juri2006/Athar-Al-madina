<?php
session_start();
require_once "database/db.php";

$sql = "SELECT ID, NAME, CATEGORY, DESCRIPTION, IMAGE_URL,
               LATITUDE AS lat, LONGITUDE AS lng, VISIT_DURATION
        FROM madinah_landmarks
        WHERE LATITUDE IS NOT NULL AND LONGITUDE IS NOT NULL
        ORDER BY NAME";
$res = mysqli_query($conn, $sql);
$places = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

$categories = [];
foreach ($places as $p) {
    $c = trim($p["CATEGORY"] ?? "");
    if ($c !== "" && !in_array($c, $categories, true)) $categories[] = $c;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>التخطيط الذكي | أثر المدينة</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;background:#f7f6f0;color:#19483e;font-family:Cairo,sans-serif}
header{background:#103f36;color:#fff;padding:15px 6%;display:flex;justify-content:space-between;align-items:center}
a{color:inherit;text-decoration:none}.logo{font-weight:800;font-size:20px}
main{max-width:1000px;width:92%;margin:35px auto 70px}
.panel{background:#fff;border:1px solid #e3e8e2;border-radius:18px;padding:25px;margin-bottom:20px}
h1,h2{margin-top:0;color:#174e45}.muted{color:#718078;font-size:13px;line-height:1.9}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.cat{display:flex;justify-content:space-between;align-items:center;border:1px solid #e1e8e2;padding:12px;border-radius:12px;gap:10px}
.cat strong{font-size:13px}.qty{display:flex;align-items:center;gap:8px}.qty button{border:0;border-radius:50%;width:30px;height:30px;background:#e8f0eb;color:#174e45;cursor:pointer;font-size:18px}
.qty output{min-width:18px;text-align:center;font-weight:bold}
button,input{font:inherit}.btn{background:#103f36;color:white;border:0;padding:12px 18px;border-radius:25px;cursor:pointer;font-weight:700;width:100%;margin-top:18px}
.location{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#f2f6f2;padding:15px;border-radius:12px;margin:15px 0}
.location button,.send{background:#174e45;color:white;border:0;border-radius:20px;padding:9px 15px;cursor:pointer}
.chat{border:1px solid #e0e7e1;border-radius:14px;overflow:hidden}
.chat-head{padding:12px;background:#eaf1ec;font-weight:700}
.messages{height:220px;overflow:auto;padding:12px;display:flex;flex-direction:column;gap:9px}
.msg{max-width:85%;padding:9px 12px;border-radius:12px;font-size:13px;line-height:1.7;background:#f0f3ef;align-self:flex-start}
.msg.user{background:#174e45;color:#fff;align-self:flex-end}
.chat-entry{display:flex;gap:8px;padding:10px;border-top:1px solid #e1e7e2}
.chat-entry input{flex:1;min-width:0;border:1px solid #dce4dd;border-radius:20px;padding:9px 13px}
@media(max-width:650px){.grid{grid-template-columns:1fr}.location{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<header><a class="logo" href="visit_planner.php">أثَر المدينة</a><a href="visit_planner.php">العودة لاختيار الوضع</a></header>
<main>
  <section class="panel">
    <small>✦ التخطيط الذكي</small>
    <h1>صمّم رحلتك في المدينة</h1>
    <p class="muted">حدد التصنيفات وعدد المعالم التي ترغب بزيارتها. سيقترح النظام معالم من قاعدة البيانات ويرتبها حسب قربها من نقطة البداية.</p>

    <div class="location">
      <div><strong>📍 نقطة البداية</strong><div class="muted" id="locationText">الموقع الافتراضي: المسجد النبوي</div></div>
      <button type="button" onclick="getLocation()">تحديد موقعي</button>
    </div>

    <h2>التصنيفات وعدد المعالم</h2>
    <div class="grid">
    <?php foreach($categories as $i=>$c): ?>
      <div class="cat">
        <strong><?= htmlspecialchars($c, ENT_QUOTES, "UTF-8") ?></strong>
        <div class="qty">
          <button type="button" onclick="changeQty(<?= $i ?>,-1)">−</button>
          <output id="qty<?= $i ?>" data-category="<?= htmlspecialchars($c, ENT_QUOTES, "UTF-8") ?>">0</output>
          <button type="button" onclick="changeQty(<?= $i ?>,1)">+</button>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <button class="btn" type="button" onclick="buildSmartRoute()">✦ اقترح لي مسارًا</button>
  </section>

  <section class="panel">
    <h2>💬 تحدث مع مساعد التخطيط</h2>
    <p class="muted">يمكنك كتابة اهتماماتك أو طلب المساعدة في اختيار التصنيفات. هذه نسخة مساعدة أولية وليست نموذج ذكاء اصطناعي متصلًا بخدمة خارجية.</p>
    <div class="chat">
      <div class="chat-head">مساعد أثر المدينة</div>
      <div class="messages" id="messages">
        <div class="msg">مرحبًا بك! هل تفضل المعالم الدينية أم التاريخية أم المتاحف والطبيعة؟ يمكنك تحديد اختياراتك من التصنيفات أعلاه.</div>
      </div>
      <form class="chat-entry" onsubmit="sendMessage(event)">
        <input id="chatInput" placeholder="اكتب سؤالك أو اهتماماتك..." autocomplete="off">
        <button class="send" type="submit">إرسال</button>
      </form>
    </div>
  </section>
</main>

<script>
const places = <?= json_encode($places, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
let start={lat:24.4672,lng:39.6111};

function changeQty(i,delta){
  const el=document.getElementById("qty"+i);
  let n=Math.max(0,Math.min(10,Number(el.textContent)+delta));
  el.textContent=n;
}
function getLocation(){
  const text=document.getElementById("locationText");
  if(!navigator.geolocation){text.textContent="المتصفح لا يدعم تحديد الموقع.";return;}
  text.textContent="جارٍ تحديد موقعك...";
  navigator.geolocation.getCurrentPosition(pos=>{
    start={lat:pos.coords.latitude,lng:pos.coords.longitude};
    text.textContent="تم تحديد موقعك بنجاح ✓";
  },()=>{text.textContent="تعذر تحديد الموقع، سيتم استخدام المسجد النبوي.";});
}
function distance(a,b){
  const rad=x=>x*Math.PI/180,R=6371;
  const dLat=rad(b.lat-a.lat),dLng=rad(b.lng-a.lng);
  const x=Math.sin(dLat/2)**2+Math.cos(rad(a.lat))*Math.cos(rad(b.lat))*Math.sin(dLng/2)**2;
  return 2*R*Math.atan2(Math.sqrt(x),Math.sqrt(1-x));
}
function optimize(list){
  let rest=[...list],out=[],pos={lat:start.lat,lng:start.lng};
  while(rest.length){
    rest.sort((a,b)=>distance(pos,{lat:+a.lat,lng:+a.lng})-distance(pos,{lat:+b.lat,lng:+b.lng}));
    const next=rest.shift();out.push(next);pos={lat:+next.lat,lng:+next.lng};
  }
  return out;
}
function buildSmartRoute(){
  const requirements=[...document.querySelectorAll("[data-category]")].map(el=>({category:el.dataset.category,count:Number(el.textContent)})).filter(x=>x.count>0);
  if(!requirements.length){alert("حدد تصنيفًا واحدًا على الأقل.");return;}
  let selected=[];
  for(const req of requirements){
    const found=places.filter(p=>String(p.CATEGORY||"").trim()===req.category);
    const sorted=found.sort((a,b)=>distance(start,{lat:+a.lat,lng:+a.lng})-distance(start,{lat:+b.lat,lng:+b.lng}));
    selected.push(...sorted.slice(0,req.count));
  }
  selected=[...new Map(selected.map(p=>[Number(p.ID),p])).values()];
  if(!selected.length){alert("لم نعثر على معالم ضمن الاختيارات.");return;}
  const route=optimize(selected);
  submitRoute(route.map(p=>p.ID));
}
function submitRoute(ids){
  const form=document.createElement("form");
  form.method="POST";form.action="route_result.php";
  const values={place_ids:JSON.stringify(ids),route_type:"ai",start_lat:String(start.lat),start_lng:String(start.lng)};
  Object.entries(values).forEach(([name,value])=>{
    const input=document.createElement("input");input.type="hidden";input.name=name;input.value=value;form.appendChild(input);
  });
  document.body.appendChild(form);form.submit();
}
function addMessage(text,who){
  const box=document.getElementById("messages"),div=document.createElement("div");
  div.className="msg"+(who==="user"?" user":"");div.textContent=text;box.appendChild(div);box.scrollTop=box.scrollHeight;
}
function sendMessage(e){
  e.preventDefault();
  const input=document.getElementById("chatInput"),msg=input.value.trim();if(!msg)return;
  addMessage(msg,"user");input.value="";
  const q=msg.toLowerCase();
  let reply="يمكنك اختيار عدد المعالم من التصنيفات أعلاه، ثم الضغط على «اقترح لي مسارًا».";
  if(q.includes("ديني")||q.includes("مسجد"))reply="لزيارة المعالم الدينية، اختر تصنيف «معالم دينية» وحدد عدد الأماكن التي ترغب بها.";
  else if(q.includes("تاريخ"))reply="للمهتمين بالتاريخ، اختر تصنيف «معالم تارخية» وحدد العدد المناسب.";
  else if(q.includes("متحف")||q.includes("ثقاف"))reply="يمكنك اختيار تصنيف «متاحف و ثقافة» لتضمين المتاحف والمعارض في رحلتك.";
  else if(q.includes("طبيعة")||q.includes("جبل")||q.includes("بستان"))reply="اختر تصنيف «أماكن طبيعية» لتضمين الأماكن الطبيعية في المسار.";
  else if(q.includes("مرحبا")||q.includes("السلام"))reply="أهلًا بك! أخبرني بنوع المعالم التي تفضلها، أو حددها من التصنيفات.";
  setTimeout(()=>addMessage(reply,"assistant"),250);
}
</script>
<?php include __DIR__ . "/translate_widget.php"; ?>
</body>
</html>