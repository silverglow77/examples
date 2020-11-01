<?php
/**
 * Created by PhpStorm.
 * User: Silver
 * Date: 05.09.2019
 * Time: 16:21
 */

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Filesystem\Filesystem;
use Carbon\Carbon;
use App\Mail\BusinessMail;
use App\Mail\TouristMail;
use Illuminate\Support\Facades\Mail;

/**
 * service all function of business invite form
 * Class BFService
 * @package App\Services
 */
class BFService
{

    /**
     * @var ff filesystem variable
     */
    public $ff;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeFiles( $request, &$data )
    {
        if ($request->file()) {
            foreach ($request->file() as $key => $val) {
                if ($val) {
                    $filename = $data['order'] . '_' . $val->getClientOriginalName();
                    $data[$key] = Storage::disk('public')->putFileAs('uploads', $val, $filename); //$val->store('uploads', 'public');
                }
            }
        }
    }


    /**
     *  get nuber of days current type visa
     * @param $kratn visa's type
     * @return int numver of days
     */
    public function getNumDays($kratn) {
        switch ($kratn) {
            case ('Однократная 30 дней') : return 30;
            case ('Однократная 90 дней') : return 90;
            case ('Двукратная 30 дней')  : return 30;
            case ('Двукратная 90 дней')  : return 90;
            case ('Многократная 6 мес')  : return 180;
            case ('Многократная 12 мес') : return 365;
        }
    }


    /**
     *  Convert Pay variant to Excel code
     *
     *  @param $key incoming Pay Variant
     *  @return Excel code
     */
    public function getPayCode ($key) {
        switch ($key) {
            case 'Платежная карта'    : return ('РК');
            case 'Электронная валюта' : return ('РК');
            case 'PayPal'             : return ('ПП');
            case 'Наличные в офисе'   : return ('НАЛ');
            case 'Интернет банкинг'   : return ('РК');
        }
    }

    /**
     * @param $data
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function ExcelCreate($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $now = Carbon::now('Europe/Moscow')->toDateTimeString();

        $sheet->setCellValue('B1', Carbon::createFromFormat('Y-m-d  H:i:s', $now)->format('d.m.Y')); //Дата заполнения
        $sheet->setCellValue('E1', strtoupper ($data['username_en'])); //Фамилия Англ
        $sheet->setCellValue('C1', strtoupper ($data['username'])); //Фамилия Рус
        $sheet->setCellValue('F1', strtoupper ($data['name_en'])); //Имя Англ
        $sheet->setCellValue('D1', strtoupper ($data['name'])); //Имя Рус
        $sheet->setCellValue('G1', strtoupper ($data['сitizen'])); //Гражданство

        $sheet->setCellValue('W1', strtoupper (explode(' ', $data['kratn'])[0])); //тип визы ОДНОКРАТНАЯ, ДВУКРАТНАЯ, МНОГОКРАТНАЯ
        $sheet->setCellValue('X1', strtoupper ($this->getNumDays($data['kratn']))); //тип визы 30, 90, 180, 365, 1095
        $sheet->setCellValue('Z1', strtoupper ($data['registration'])); //Срочность оформления
        $sheet->setCellValueByColumnAndRow(28,1, strtoupper ($data['task'])); //Цель поездки  'AB1'
        $sheet->setCellValueByColumnAndRow(27,1, strtoupper ($data['invite'])); //Форма приглашения
       // dd($data);
        $sheet->setCellValue('U1', strtoupper ($data['dateopenvisa'])); //Дата открытия визы
        $sheet->setCellValue('J1', strtoupper (Carbon::createFromFormat('Y-m-d', $data['birthday'])->format('d.m.Y'))); //Дата рождения
        $sheet->setCellValue('N1', strtoupper ($data['sex'])); //Пол

        $sheet->setCellValue('K1', strtoupper ($data['passnum']));      //Номер паспорта
        $sheet->setCellValue('L1', strtoupper (Carbon::createFromFormat('Y-m-d', $data['passissue'])->format('d.m.Y')));    //Дата выдачи
        $sheet->setCellValue('M1', strtoupper (Carbon::createFromFormat('Y-m-d', $data['passvalidity'])->format('d.m.Y'))); //Действителен до

        $sheet->setCellValue('R1', strtoupper ($data['citiesRF'])); //Города посещения в РФ
        $sheet->setCellValueByColumnAndRow(47,1, strtoupper ($data['addr']));    //Адрес проживания в РФ

        $sheet->setCellValue('S1', strtoupper ($data['request_country'])); //Страна обращения за визой
        $sheet->setCellValue('T1', strtoupper ($data['request_city']));   //Город обращения за визой

        $sheet->setCellValue('H1', strtoupper ($data['living_country']. ', '.$data['living_city'] )); //Страна, Город проживания
        $sheet->setCellValue('I1', strtoupper ($data['birth_country']. ', '.$data['birth_city'] ));   //Страна, Город рождения

        $sheet->setCellValue('O1', strtoupper ($data['organization_name']));    //Название организации
        $sheet->setCellValue('P1', strtoupper ($data['office_position']));       //Должность
        $sheet->setCellValue('Q1', strtoupper ($data['legal_address']));        //Адрес организации

        if ($data['payways'] == 'fizlic') {
            $sheet->setCellValueByColumnAndRow(37, 1, strtoupper($this->getPayCode($data['payform'])));     //Вариант оплаты физ
        } else {
            $sheet->setCellValueByColumnAndRow(37, 1, 'ЮР');                                  //Вариант оплаты Юр

            $sheet->setCellValueByColumnAndRow(53, 1, strtoupper($data['leg_company_name']));      // НАЗВАНИЕ КОМПАНИИ
            $sheet->setCellValueByColumnAndRow(54, 1, strtoupper($data['leg_inn']));               // ИНН
            $sheet->setCellValueByColumnAndRow(55, 1, strtoupper($data['leg_actual_addr']));       // АДРЕС
            $sheet->setCellValueByColumnAndRow(56, 1, strtoupper($data['leg_bik']));               // БИК БАНКА
            $sheet->setCellValueByColumnAndRow(57, 1, strtoupper($data['leg_checking_account']));  // Р/С
        }

        $sheet->setCellValueByColumnAndRow(40, 1, strtoupper ($data['email']));    //E-mail
        $sheet->setCellValueByColumnAndRow(42, 1, strtoupper ($data['phone']));    //Телефон заказчика
        $sheet->setCellValueByColumnAndRow(41, 1, strtoupper ($data['fio']));      //Контакное лицо
        $sheet->setCellValueByColumnAndRow(43, 1, strtoupper ($data['comments'])); //Комментарий
        $sheet->setCellValueByColumnAndRow(38, 1, intval ($data['order']));        //Номер заказа

        $writer = new Xlsx($spreadsheet);
        $writer->save('storage/uploads/' . $data['order'] . '_order.xlsx');
    }


    /**
     * @param $order - current order's num
     * @return array - list of attaching files
     */
    public function getFileList ($order) {
        $fnames = [];
        $this->ff = new Filesystem;
        //$files = Filesystem::files('storage/uploads');
        $files = $this->ff->files('storage/uploads');
        foreach ($files as $file) {
            $fname = explode('_', pathinfo($file)['basename']);
            if ($fname[0] == $order) {
                $fnames[] = pathinfo($file)['dirname'] .'/'. pathinfo($file)['basename'];
            }
        }
        return $fnames;
    }

    /**
     * @param $files
     */
    public function deleteFiles ($files)
    {
        if ($files)
            foreach ($files as $file)
                $this->ff->delete($file);
    }

    /**
     * @param $data
     */
    public function sendMail($data, $type)
    {
        $filelist = $this->getFileList($data['order']);

        if ($type == 'tourist') {
            $data['birthday'] = Carbon::createFromFormat('Y-m-d', $data['birthday'])->format('d.m.Y');
            $data['issued'] = Carbon::createFromFormat('Y-m-d', $data['issued'])->format('d.m.Y');
            $data['expired'] = Carbon::createFromFormat('Y-m-d', $data['expired'])->format('d.m.Y');
        } else {
            $data['birthday'] = Carbon::createFromFormat('Y-m-d', $data['birthday'])->format('d.m.Y');
            $data['passissue'] = Carbon::createFromFormat('Y-m-d', $data['passissue'])->format('d.m.Y');
            $data['passvalidity'] = Carbon::createFromFormat('Y-m-d', $data['passvalidity'])->format('d.m.Y');
        }

        if ($type == 'business' || $type == 'guest' || $type == 'letter') {
            $ret = Mail::to('Artem@visardo.ru')->send(new BusinessMail($data, $filelist, $type));
        }
        elseif ($type == 'tourist') {
            $ret = Mail::to('Artem@visardo.ru')->send(new TouristMail($data, $filelist, $type));
        }
        $this->deleteFiles($filelist);
    }
}

