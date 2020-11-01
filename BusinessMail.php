<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;



use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BusinessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $maildata;
    public $files;
    public $type;

    public function __construct($maildata, $filelist, $type)
    {
        $this->maildata = $maildata;
        $this->files = $filelist;
        $this->type = $type;
    }


    /**
     * @return string  visa code
     */
    public function getCodeSubj() {
        $subj='';
        switch ($this->maildata['kratn']) {
            case ('Однократная 30 дней') : $subj = 'O1'; break;
            case ('Однократная 90 дней') : $subj = 'O3'; break;
            case ('Двукратная 30 дней') : $subj = 'D1'; break;
            case ('Двукратная 90 дней') : $subj = 'D3'; break;
            case ('Многократная 6 мес') : $subj = 'M6'; break;
            case ('Многократная 12 мес') : $subj = 'M12'; break;
            case ('Многократная 36 мес') : $subj = 'M36'; break;
        }

        if ($this->maildata['registration'] == 'Express') {
            $subj = $subj . '(E)';
        } elseif ($this->maildata['registration'] == 'Normal') {
            $subj = $subj . '(N)';
        } elseif ($this->maildata['registration'] == 'LExpress') {
            $subj = $subj . '(L)';
        }
        return $subj;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $datamail = $this->maildata;
        $mailtype = '';
        $subject = $datamail['username'] . ' ' . $datamail['name'] . ', ' . $datamail['сitizen'] . ', ' .
                    $datamail['task'].',';

        if ($this->type == "letter") {
            $subject .= ' Письмо, ';
            $mailtype = 'Письмо';
        } else {
            if ($datamail['invite'] == 'e-mail') {
                $subject .= ' Электронное, ';
                $mailtype = 'Электронное';
            } elseif ($datamail['invite'] == 'blank') {
                $subject .= ' Бланк, ';
                $mailtype = 'Бланк';
            }
        }

        $subject .= $this->getCodeSubj();

        $message = $this->view('mails.businessmail')
            ->from('online@visardo.ru', $datamail["order"].' ')
            //    ->text('This is the body in plain text for non-HTML mail clients')
            ->subject($subject)
            ->with(compact('datamail', 'mailtype'));

        if ($this->files)
            foreach ($this->files as $file)
                $message->attach($file);

        return $message;
    }
}
