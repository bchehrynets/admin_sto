<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\Employee;
use App\Employee_fine;
use App\Employee_balance;
use App\Employee_balance_log;
use App\Coffee_token_log;
use App\Employees_notes;


class EmployeesAdminController extends Controller
{
    /* Список всех сотрудников */
    public function view_employees(){
        $employee_data = Employee::where('status', 'active')->get();


        return view('employees_admin.employees_admin_index', ['employee_data' => $employee_data]);

    }

    /* Страница добавления сотрудника */
    public function add_employee(){
        return view('employees_admin.add_employee');
    }

    /* Обработка POST запроса добавления сотрудника */
    public function add_employee_post(Request $request){
        /* Создать аккаунт под сотрудника */
        $login = $request->login;
        $password = $request->password;
        $new_user = new User();
        $new_user->name = $login;
        $new_user->password = Hash::make($password);
        $new_user->email = $login.'@test.com';
        $new_user->role = 'employee';
        $new_user->general_name = $request->name.' '.$request->surname;
        $new_user->save();
        $new_user_id = $new_user->id;
        
        /* Создать нового сотрудника */
        $new_employee = new Employee();
        $new_employee->general_name = $request->name.' '.$request->surname;
        $new_employee->status = 'active';
         /* Добавляем в таблицу работников ID соответствующего юзера */
        $new_employee->user_id = $new_user_id; 
        $new_employee->save();

        /* Добавить ему нулевой баланс */
        $new_employee_id = $new_employee->id;        
        $new_employee_balance = new Employee_balance();
        $new_employee_balance->balance = 0;
        $new_employee_balance->employee_id = $new_employee_id;
        $new_employee_balance->save();

        /* Вернуться ко списку сотрудников */
        return redirect()->route('view_employees');
    }

    /* Страница статусов сотрудника */
    public function manage_employee_status($employee_id){
        $employee = Employee::find($employee_id);

        return view('employees_admin.manage_employee_status', ['employee' => $employee]);
    }

    /* Действие перевода сотрудника в архив */
    public function archive_employee(Request $request){
        /* Перевести сотрудника в архив */

        $employee = Employee::find($request->employee_id);
        $employee->status = 'archived';
        $employee->save();
        
        /* Вернуться ко списку сотрудников */
        return redirect()->route('view_employees');
    }
    
    /* Общая страница финансов по работнику */
    public function employee_finances($employee_id){
        $employee = Employee::find($employee_id);
        $balance = $balance_dump->balance;
        return view('employees_admin.employee_finances_admin', ['employee' => $employee, 'balance' => $balance]);
    }

    /* - Добавления примечания к сотруднику: страница - */
    public function add_note_to_employee_page($employee_id){
        $employee = Employee::find($employee_id);
        return view('employees_admin.add_note_to_employee', ['employee' => $employee]);
    }

    /* - Добавление примечания к сотруднику: POST - */
    public function add_note_to_employee_post(Request $request){
        //Добавить примечание
        $employee = Employee::find($request->employee_id);
        $new_employee_note_entry = new Employees_notes();
        $new_employee_note_entry->employee_id = $employee->id;
        $new_employee_note_entry->author_id = Auth::user()->id;
        $new_employee_note_entry->text = $request->note_content;
        $new_employee_note_entry->type = 'note';
        $new_employee_note_entry->save();

        //Возврат на страницу сотрудника
        return redirect ('/admin/employee/' .$employee->id);
    }     

    /* - Удление примечания к сотруднику - */
    public function delete_employee_note($note_id){
        Employees_notes::find($note_id)->delete();
        return back();
    }

    /* Изменение ставки сотрудника */
    public function change_standard_shift_wage(Request $request){
        // Задаём новую ставку
        Employee::find($request->employee_id)->set_new_wage($request->new_wage);
        
        // Возвращаемся на предыдущую страницу
        return back();
    }

    public function single_employee_notes($employee_id){
        $employee = Employee::find($employee_id);

        $employee_notes = DB::table('employees_notes')->where('employee_id', $employee->id)->get();

        foreach($employee_notes as $employee_note){
            $employee_note->author_name = User::find($employee_note->author_id)->general_name;
        }

        return view('employees_admin.single_employee_notes', [
            'employee' => $employee,
            'employee_notes' => $employee_notes
        ]);
    }

        

    

    /*
    ********** Блок начислений (credit) **********
    */

    public function employee_credit_page($employee_id){
        $employee = Employee::find($employee_id);

        return view('employees_admin.employee_credit_page', ['employee' => $employee]);

    }

    /*
    ********** Блок со штрафами **********
    */

    /* Страница начисления штрафов */
    public function view_employee_fines($employee_id){
        $employee = Employee::find($employee_id);

        $fines = 
            DB::table('employee_fines')
                ->where([
                        ['employee_id', '=', $employee_id], ['status', '=', 'pending']
                    ])
                ->get();

        return view('employees_admin.employee_fines_admin',
            [
                'employee' => $employee,
                'fines' => $fines
            ]
        );

    }
    
    /* Применить штраф */
    public function apply_fine($fine_id){
        $fine = Employee_fine::find($fine_id);
        // Перевести статус в применённые
        $fine->status = 'applied';
        $fine->save();

        // Вычесть из баланса сумму штрафа
        $employee_balance = DB::table('employee_balances')
            ->where('employee_id', '=', $fine->employee_id)
            ->first();
        
        $new_balance = $employee_balance->balance - $fine->amount;

        DB::table('employee_balances')
            ->where('employee_id', '=', $fine->employee_id)
            ->update(['balance' => $new_balance]);

        // Редирект на страницу штрафов сотрудника
        return redirect()->route('employee_fines', ['employee_id' => $fine->employee_id]);


    }

    /* Отменить штраф */
    public function quash_fine($fine_id){
        $fine = Employee_fine::find($fine_id);
        $fine->status = 'quashed';
        $fine->save();

        // Редирект
        return redirect()->route('employee_fines', ['employee_id' => $fine->employee_id]);
    }

    /* Добавить штраф вручную */

    public function add_fine_manually(Request $request){
        /* Добавление штрафа в режиме "ожидает применения" */
        $new_fine = new Employee_fine;
        $new_fine->employee_id = $request->employee_id;
        $new_fine->amount = $request->fine_amount;
        $new_fine->reason = $request->fine_reason;
        $new_fine->status = 'pending';
        $new_fine->date = date('Y-m-d');
        $new_fine->save();

        /* Редирект на страницу штрафов */
        return redirect()->route('employee_fines', ['employee_id' => $request->employee_id]);


    }

    /*
    ********** Блок с жетонами кофе **********
    */
    
    /* Страница "жетоны кофе" */
    public function employee_coffee_token_index($employee_id){
        $employee = Employee::find($employee_id);
        // Получаем данные о последних 10 операциях выдачи жетонов по этому сотруднику
        $token_logs = Coffee_token_log::where('employee_id', $employee_id)->orderBy('created_at', 'desc')->get();
        return view('employees_admin.employee_coffee_tokens', ['employee' => $employee, 'token_logs' => $token_logs]);
    }

    /* Выдать жетон на кофе */
    public function employee_coffee_token_issue(Request $request){
        $request->validate([
            'token_count' => "required|numeric"
        ]);
        
        $employee_id = $request->employee_id;
        $token_count = $request->token_count;
        
        // Вычесть стоимость жетонов с баланса
        $token_price = 5; // Сделать подтягивание с базы
        $token_total = $token_price * $token_count;
        $employee_balance = Employee_balance::where('employee_id', $employee_id)->first();
        $employee_balance->balance = $employee_balance->balance - $token_total;
        $employee_balance->save();

        // Добавить жетоны в историю
        $employee_coffee_log_entry = new Coffee_token_log;
        $employee_coffee_log_entry->token_count = $token_count;
        $employee_coffee_log_entry->date = date('Y-m-d');
        $employee_coffee_log_entry->employee_id = $employee_id;
        $employee_coffee_log_entry->save();

        // Добавить запись в общие логи
        $employee_balance_log = new Employee_balance_log;
        $employee_balance_log->amount = $token_total;
        $employee_balance_log->reason = 'Списание за выдачу жетонов кофе';
        $employee_balance_log->action = 'withdrawal';
        $employee_balance_log->source = 'auto';
        $employee_balance_log->date = date('Y-m-d');
        $employee_balance_log->employee_id = $employee_id;
        $employee_balance_log->save();


        // Сделать редирект на страницу финансов пользователя
        return redirect()->route('employee_finances_admin', ['employee_id' => $employee_id]);


    }

    /*
    ********** Архив сотрудников **********
    */

    /* Отобразить архив сотрудников */
    public function show_employee_archive(){
        $archived_employees = Employee::where('status', 'archived')->get();

        return view('employees_admin.employee_archive', ['archived_employees' => $archived_employees]);
    }
}
