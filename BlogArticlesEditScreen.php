<?php

namespace App\Orchid\Screens\Visa;

use Illuminate\Http\Request;

use App\Models\VisardoPost;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;

use Orchid\Screen\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\CheckBox;

use Carbon\Carbon;

class BlogArticlesEditScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Добавление статьи';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'Добавление новой статьи в блог';


    /**
     * @var bool
     */
    public $exists = false;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(VisardoPost $post): array
    {
        $this->exists = $post->exists;

        if($this->exists){
            $this->name = 'Редактирование статьи';
            $this->description = 'Редактирование данных по текущей статье блога';
        }

        $pic = $post->attachments()->where('post_type','pic')->first();
       // dd($pic);

        return [
            'post' => $post,
            'pic'  => $pic
        ];
    }

    /**
     * Button commands.
     *
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            Button::make('Отмена')
                ->icon('icon-close')
                ->method('createCancel'),

            Button::make('Создать')
                ->icon('icon-pencil')
                ->method('create')
                ->canSee(!$this->exists),

            Button::make('Сохранить')
                ->icon('icon-note')
                ->method('update')
                ->canSee($this->exists),

            Button::make('Удалить')
                ->icon('icon-trash')
                ->method('remove')
                ->canSee($this->exists),
        ];
    }

    /**
     * Views.
     *
     * @return Layout[]
     */
    public function layout(): array
    {
        return [
            Layout::columns([
                Layout::rows([
                    Input::make('post.post_date')
                        ->title('Время создания')
                        ->disabled(true),
                    Input::make('post.post_title')
                        ->title('Имя статьи'),
                ]),
                Layout::rows([
                    Input::make('post.post_modified')
                        ->title('Время последнего изменения')
                        ->disabled(true),
                    Input::make('post.post_name')
                        ->title('Имя ссылки'),
                ]),
            ]),

            Layout::columns([
                Layout::rows([
                    Select::make('post.post_status')
                        ->options([
                            'publish' => 'publish',
                            'inherit' => 'inherit'
                        ])
                        ->title('Режим публикации статьи')
                ]),
                Layout::rows([
                    Input::make('post.post_author')
                        ->title('Автор статьи'),
                ]),
                Layout::rows([
                    Input::make('post.post_parent')
                        ->title('Parent'),
                ]),
                Layout::rows([
                    Input::make('post.comment_count')
                        ->title('Количество просмотров'),
                ]),
            ]),

            Layout::columns([
                Layout::rows([
                    Cropper::make('pic.guid')
                        ->width(500)
                        ->height(300)
                        ->maxFileSize(2)
                        ->title('Изображение для статьи')
                        ->targetRelativeUrl()
                ])
            ]),

            Layout::columns([
                Layout::rows([
                    Quill::make('post.post_content')
                        ->title('Редактирование текста статьи'),
                ])
            ])
        ];
    }


    /**
     * @param VisardoPost    $post
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(VisardoPost $post, Request $request)
    {
        //dd($request);
        $data = $request->get('post');
        $data['post_date_gmt'] = $data['post_modified_gmt'] = Carbon::now()->timezone('UTC');
        $data['post_excerpt']  = '';
        $data['to_ping']       = '';
        $data['pinged']        = '';
        $data['post_type']     = 'post';
        $data['post_content_filtered'] = '';

        $post->fill($data)->save();

        $data = $request->get('pic');
        if ($data['guid']) { // если есть картинка
            $data['post_date_gmt'] = $data['post_modified_gmt'] = Carbon::now()->timezone('UTC');
            $data['post_excerpt'] = '';
            $data['to_ping'] = '';
            $data['pinged'] = '';
            $data['post_type'] = $data['post_title'] = $data['post_content'] = 'pic';
            $data['post_name'] = $post->post_name . '_pic';
            $data['post_parent'] = $post->ID;
            $data['post_content_filtered'] = '';

            $pic = new VisardoPost;
            $pic->fill($data)->save();
        }

        Alert::info('Вы успешно создали новую сттью');
        return redirect()->route('platform.article.list');
    }

    public function update(VisardoPost $post, Request $request)
    {
        $pic = $post->attachments()->where('post_type','pic')->first();

        if ($pic) {
            $data = $request->get('pic');
            if ($data['guid']) { // если удалить картинку то удаляем и существующую запись
                $pic->fill($request->get('pic'))->save();
            } else {
                $pic->delete();
            }
        } else {
            $data = $request->get('pic');
            if ($data['guid']) { // если есть картинка
                $data['post_date_gmt'] = $data['post_modified_gmt'] = Carbon::now()->timezone('UTC');
                $data['post_excerpt'] = '';
                $data['to_ping'] = '';
                $data['pinged'] = '';
                $data['post_type'] = $data['post_title'] = $data['post_content'] = 'pic';
                $data['post_name'] = $post->post_name . '_pic';
                $data['post_parent'] = $post->ID;
                $data['post_content_filtered'] = '';
                //dd('else', $data);
                $pic = new VisardoPost;
                $pic->fill($data)->save();
            }
        }

        $post->fill($request->get('post'))->save();
        Alert::info('Вы успешно обновили статью');
        return redirect()->route('platform.article.list');
    }

    /**
     * @param CountryModel    $country
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(VisardoPost $post)
    {
        //$pic = VisardoPost::where('post_type', 'pic')->where('post_parent', $post->ID);
        $pic = $post->attachments()->where('post_type','pic')->first();
        if ($pic)
            $pic->delete();

        $post->delete()
            ? Alert::info('Вы удалили статью')
            : Alert::warning('Какая то ошибка')
        ;

        return redirect()->route('platform.article.list');
    }

    public function createCancel(){
        Alert::info('Вы отменили редактирование статьи');
        return redirect()->route('platform.article.list');
    }

}
