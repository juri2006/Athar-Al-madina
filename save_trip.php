<?php
session_start();
require_once "database/db.php";

header('Content-Type: application/json; charset=utf-8');

$userId=$_SESSION['user_id']??null;
if(!$userId){
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'يرجى تسجيل الدخول.'],JSON_UNESCAPED_UNICODE);
    exit;
}

function respond(bool $success,string $message,int $status=200): void {
    http_response_code($status);
    echo json_encode(['success'=>$success,'message'=>$message],JSON_UNESCAPED_UNICODE);
    exit;
}

$isJson=stripos($_SERVER['CONTENT_TYPE']??'','application/json')!==false;
$data=$isJson?json_decode(file_get_contents('php://input'),true):$_POST;
if(!is_array($data))$data=[];

$action=$data['action']??'save';

if($action==='delete'){
    $tripId=(int)($data['trip_id']??0);
    if($tripId<1)respond(false,'رقم المسار غير صالح.',400);

    $conn->begin_transaction();
    try{
        // تحقق من الملكية قبل الحذف
        $stmt=$conn->prepare("SELECT id FROM trips WHERE id=? AND user_id=? FOR UPDATE");
        $stmt->bind_param('ii',$tripId,$userId);
        $stmt->execute();
        $owned=$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$owned){
            $conn->rollback();
            respond(false,'المسار غير موجود أو لا تملك صلاحية حذفه.',404);
        }

        $stmt=$conn->prepare("DELETE FROM trip_places WHERE trip_id=?");
        $stmt->bind_param('i',$tripId);$stmt->execute();$stmt->close();

        $stmt=$conn->prepare("DELETE FROM trips WHERE id=? AND user_id=?");
        $stmt->bind_param('ii',$tripId,$userId);$stmt->execute();
        $stmt->close();

        $conn->commit();
        respond(true,'تم حذف المسار بنجاح.');
    }catch(Throwable $e){
        $conn->rollback();
        error_log($e->getMessage());
        respond(false,'حدث خطأ أثناء حذف المسار.',500);
    }
}

if($action!=='save')respond(false,'العملية غير معروفة.',400);

$tripName=trim((string)($data['trip_name']??'المسار الحالي'));
$ids=$data['place_ids']??($_SESSION['current_trip']['place_ids']??[]);
if(!is_array($ids))respond(false,'قائمة المعالم غير صالحة.',400);

$ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($id)=>$id>0)));
if(!$ids)respond(false,'لا يمكن حفظ مسار بلا معالم.',400);
if(count($ids)>100)respond(false,'عدد المعالم يتجاوز الحد المسموح.',400);

$marks=implode(',',array_fill(0,count($ids),'?'));
$types=str_repeat('i',count($ids));
$stmt=$conn->prepare("SELECT ID, VISIT_DURATION FROM madinah_landmarks WHERE ID IN ($marks)");
$stmt->bind_param($types,...$ids);
$stmt->execute();
$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$durations=[];
foreach($rows as $row)$durations[(int)$row['ID']]=(int)($row['VISIT_DURATION']??0);
foreach($ids as $id){
    if(!array_key_exists($id,$durations))respond(false,'أحد المعالم المحددة غير موجود.',400);
}

$totalDuration=array_sum(array_map(fn($id)=>$durations[$id],$ids));
$startLocation='المدينة المنورة';

$conn->begin_transaction();
try{
    $stmt=$conn->prepare(
        "INSERT INTO trips (user_id,trip_name,start_location,total_duration)
         VALUES (?,?,?,?)"
    );
    $stmt->bind_param('issi',$userId,$tripName,$startLocation,$totalDuration);
    $stmt->execute();
    $tripId=$conn->insert_id;
    $stmt->close();

    $stmt=$conn->prepare(
        "INSERT INTO trip_places
         (trip_id,landmark_id,visit_order,planned_arrival,planned_duration)
         VALUES (?,?,?,NULL,?)"
    );
    foreach($ids as $index=>$landmarkId){
        $order=$index+1;
        $duration=$durations[$landmarkId];
        $stmt->bind_param('iiii',$tripId,$landmarkId,$order,$duration);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();

    unset($_SESSION['current_trip']);
    respond(true,'تم حفظ المسار بنجاح.');
}catch(Throwable $e){
    $conn->rollback();
    error_log($e->getMessage());
    respond(false,'تعذر حفظ المسار، تحقق من إعدادات قاعدة البيانات.',500);
}