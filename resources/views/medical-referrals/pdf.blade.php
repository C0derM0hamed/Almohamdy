<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;direction:rtl;color:#111}h1{text-align:center;font-size:22px;margin-bottom:28px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #777;padding:8px;text-align:right}th{background:#eee;width:34%}.footer{text-align:center;margin-top:36px;color:#555}</style></head><body>
<h1>{{ $definition['title'] }}</h1><table>
@foreach((array)$item as $key => $value)
@continue(in_array($key, ['companies_groups_id','group_id','branch_id','user_id','created_at'], true))
<tr><th>{{ ['id'=>'الرقم','patient_name'=>'اسم المريض','name'=>'الاسم','age'=>'العمر','idno'=>'رقم الهوية','no'=>'رقم الهوية','gender'=>'الجنس','room_type'=>'القسم / الوحدة','doctor'=>'الطبيب','date'=>'تاريخ الإصدار','booking_period'=>'المدة','letter_side'=>'جهة الخطاب','nationality'=>'الجنسية','contact_number'=>'رقم التواصل','ehala_number'=>'رقم الإحالة','apology'=>'سبب الاعتذار','Report_number'=>'رقم البلاغ','date_dlivry'=>'تاريخ استلام الحالة','Notification_date'=>'تاريخ الإشعار','status'=>'حالة الطلب','create_at'=>'التاريخ'][$key] ?? $key }}</th><td>{{ $key === 'date' && is_numeric($value) ? date('Y-m-d g:i A',(int)$value) : $value }}</td></tr>
@endforeach
</table><div class="footer">مجموعة مستشفيات الحمادي</div></body></html>
