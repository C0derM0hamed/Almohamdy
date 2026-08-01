<?php
namespace Tests\Feature;
use App\Services\AdmissionCalculator\AdmissionCalculatorService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class AdmissionCalculatorTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']); app('db')->purge('sqlite'); foreach(['admission_rooms','admission_nationality'] as $t) Schema::create($t,function(Blueprint $b){$b->increments('id');$b->integer('admission_status_id')->default(2);$b->string('name_ar')->nullable();$b->string('price')->nullable();$b->tinyInteger('publish')->default(1);}); foreach(['admission_calculator','manual_admission_calculator'] as $t) Schema::create($t,function(Blueprint $b){$b->increments('id');$b->integer('branch_id');$b->integer('companies_groups_id');$b->integer('user_id');$b->string('patient_name');$b->string('file_number')->nullable();$b->integer('nationality');$b->integer('room');$b->string('procedurs')->nullable();$b->string('doctor');$b->string('date');$b->integer('days');$b->string('discount')->nullable();$b->string('tools_value')->nullable();$b->string('lang')->nullable();$b->string('vat')->nullable();$b->string('code_total')->nullable();$b->integer('type')->default(0);$b->string('room_price')->nullable();}); DB::table('admission_rooms')->insert(['id'=>1,'admission_status_id'=>2,'name_ar'=>'غرفة','price'=>'100','publish'=>1]); DB::table('admission_nationality')->insert(['id'=>1,'name_ar'=>'سعودي','publish'=>1]); session(['hr_user_id'=>10,'hr_branch_id'=>1,'companies_groups_id'=>1]); }
    public function test_standard_and_manual_estimates_are_scoped(): void { $s=app(AdmissionCalculatorService::class); $data=['patient_name'=>'مريض','file_number'=>'A1','nationality'=>1,'room'=>1,'procedurs'=>'إجراء','doctor'=>'طبيب','days'=>2,'discount'=>0,'tools_value'=>0,'lang'=>'ar']; $id=$s->create('standard',$data); $manual=$s->create('manual',$data); $this->assertSame('مريض',$s->find('standard',$id)->patient_name); $this->assertSame('مريض',$s->find('manual',$manual)->patient_name); session(['hr_branch_id'=>2]); $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class); $s->find('standard',$id); }
}
