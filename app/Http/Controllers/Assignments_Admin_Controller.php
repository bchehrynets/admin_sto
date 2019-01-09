<?php
/*
* Контроллер админ версии нарядов
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use App\Employee;
use App\Client;
use App\Cars_in_service;
use App\Assignment;
use App\Sub_assignment;
use App\Workzone;

class Assignments_Admin_Controller extends Controller
{
    
    /* Отображения списка всех нарядов */
    public function assignments_index(){
        /* Получаем всю нужную информацию по нарядам */
        $assignments_data =
            DB::table('assignments')
                ->join('employees', 'assignments.responsible_employee_id', '=', 'employees.id')
                ->join('cars_in_service', 'assignments.car_id', '=', 'cars_in_service.id')
                ->select(
                        'assignments.*',
                        'employees.general_name AS employee_name',
                        'cars_in_service.general_name AS car_name'
                    )
                ->get();

        return view('assignments_admin.assignments_admin_index', ['assignments' => $assignments_data]);
    }

    /* Добавления наряда: страница с формой */
    public function add_assignment_page($car_id){
        
        $client = Client::get_client_by_car_id($car_id);
        
        /* Собираем информация по сотрудникам, которых можно указать как ответственных */
        $employees = Employee::where('status', 'active')->get();

        /* Информация о машине */
        $car = Cars_in_service::find($car_id);
        
        return view(
            'admin.assignments.add_assignment_page',
            [
                'employees' => $employees,
                'owner' => $client,
                'car' => $car
            ]
        );
    }
    
    /* Добавления наряда: страница обработки POST данных*/
    public function add_assignment_page_post(Request $request){
        /* Получаем данные из запроса */
        $responsible_employee_id = $request->responsible_employee_id;
        $assignment_description = $request->assignment_description;
        $car_id = $request->car_id;

        /* Создаём новый наряд и сохраняем его*/
        $new_assignment = new Assignment();
        $new_assignment->responsible_employee_id = $responsible_employee_id;
        $new_assignment->description = $assignment_description;
        $new_assignment->car_id = $car_id;
        $new_assignment->date_of_creation = date('Y-m-d');
        $new_assignment->status = 'active';
        $new_assignment->save();


        /* Возвращаемся на страницу нарядов по авто */
        return redirect('admin/cars_in_service/view/'.$car_id);
    }

    /* Просмотр наряда : страница */
    public function view_assignment($assignment_id){
        /* Получаем информацию по наряду */
        $assignment = Assignment::find($assignment_id);

        /* Получаем список зональных нарядов */
        $sub_assignments = 
            DB::table('sub_assignments')
            ->where('assignment_id', $assignment_id)
            ->join('workzones', 'sub_assignments.workzone_id', '=', 'workzones.id')
            ->select('sub_assignments.*', 'workzones.general_name')
            ->get();

        /* Собираем допонлительные данные */
        foreach($sub_assignments as $sub_assignment){
            /* Название рабочей зоны */
            $sub_assignment->workzone_name = Workzone::find($sub_assignment->workzone_id)->general_name;
            
            /* Имя ответственного работника */
            $sub_assignment->responsible_employee = Employee::find($sub_assignment->responsible_employee_id)->general_name;
        }

        /* Получаем список картинок по наряду */
        $images = [];
        foreach(Storage::files('public/'.$assignment_id) as $file){
             $images[] = $file;
        }
        

        /* Возвращаем представление */
        return view('admin.assignments.view_assignment_page',
            [
                'assignment' => $assignment,
                'sub_assignments' => $sub_assignments,
                'image_urls'=> $images,                
            ]);
    }

    /* Изменение названия наряда */
    public function change_assignment_name(Request $request){
        /* Меняем название наряда */
        $assignment_id = $request->assignment_id;
        $new_name = $request->new_name;

        $assignment = Assignment::find($assignment_id);
        $assignment->description = $new_name;
        $assignment->save();
        
        /* Возвращаемся на страницу наряда */
        return back();
    }

    /* Добавление зонального наряда : страница */
    public function add_sub_assignment_page($assignment_id){
        /* Получаем данные по основному наряду */
        $assignment = Assignment::find($assignment_id);

        /* Данные по рабочим зонам */
        $workzones = Workzone::all();

        /* Данные по сотрудникам, которых можно сделать ответственными */
        $employees = Employee::all();

        /* Возвращаем страницу добавления зонального наряда */
        return view('admin.assignments.add_sub_assignment_page', [
            'assignment' => $assignment, // "Родительский" наряд
            'workzones' => $workzones, //  Рабочие зоны
            'employees' => $employees // Сотрудники
        ]);
    }

    /* Добавление зонального наряда : POST */
    public function add_sub_assignment_post(Request $request){
        
        /* Получаем данные из запроса */
        $main_assignment_id = $request->assignment_id; // ID "родительского" наряда
        $sub_assignment_name = $request->name; // Название зонального наряда
        $sub_assignment_description = $request->description; // Описание зонального наряда
        $workzone_id = $request->workzone; // ID рабочей зоны
        $responsible_employee_id = $request->responsible_employee; // ID ответственного лица (employee.id)
        
        /* Создание нового зонального наряда */
        $sub_assignment = new Sub_assignment();
        $sub_assignment->assignment_id = $main_assignment_id;
        $sub_assignment->name = $sub_assignment_name;
        $sub_assignment->description = $sub_assignment_description;
        $sub_assignment->workzone_id = $workzone_id;
        $sub_assignment->responsible_employee_id = $responsible_employee_id;
        $sub_assignment->date_of_creation = date('Y-m-d');
        $sub_assignment->save();

        /* Возвращаемся на страницу */
        return redirect('/admin/assignments/view/'.$main_assignment_id);
    }

    /* Добавление фотографий к наряду : Страница */
    public function add_photo_to_assignment_page($assignment_id){
        /* Получаем текущий наряд, к которому будут добавляться фото */
        $assignment = Assignment::find($assignment_id);
        
        /* Отображаем представление */
        return view('admin.assignments.add_photo_to_assignment_page', ['assignment' => $assignment]);
    }

    /* Добавление фотографий к наряду : Обработка запроса */
    public function add_photo_to_assignment_post(Request $request){
        /* Получаем данные из запроса */
        /* ID наряда, к которому добавляем фото */
        $assignment_id = $request->assignment_id;

        /* Сохраняем фото */
        $request->test->store('public/'.$assignment_id);
        
        /* Возвращаемся на страницу авто */
        return redirect('admin/assignments/view/'.$assignment_id);
    }

    /* Удаление фотографий : страница */
    public function delete_photos_page($assignment_id){
        /* Получить список фотографий по наряду */
        $images = [];
        foreach(Storage::files('public/'.$assignment_id) as $file){
             $images[] = $file;
        }
        
        /* Вывести страницу */
        return view('admin.assignments.delete_photos_from_assignment_page', ['images' => $images, 'assignment_id' => $assignment_id]);
    }

    /* Удаление фотографий : POST */
    public function delete_photos_post(Request $request){
        /* Удалить фото */
        Storage::delete($request->path_to_file);
        
        /* Вернуться на страницу удаления фотографий */
        return redirect('admin/assignments/'.$request->assignment_id.'/delete_photos_page');
    }

}
