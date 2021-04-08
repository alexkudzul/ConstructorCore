<?php

namespace Tiuswebs\ConstructorCore;

use Tiuswebs\ConstructorCore\Inputs\Select;
use Tiuswebs\ConstructorCore\Inputs\Number;
use Illuminate\Support\Facades\Http;

class Result extends Core
{
    public $default_limit = 10;
    public $default_sort = 'latest';
    public $default_result = null;
    public $show_options = true;
    public $include_options = null;
    public $exclude_options = null;

    public function load()
    {
        $relation = $this->values->show;
        $sort = $this->values->sort;
        if($relation=='contents' && !is_bool($this->contents)) {
            $this->elements = $this->contents->$sort()->paginate($this->values->limit);
        } else {
            $url = config('app.tiuswebs_api');
            $url = "{$url}/api/example_data/{$relation}";
            $elements = collect(json_decode(Http::get($url)->body()));
            if($this->values->sort=='latest') {
                $elements = $elements->sortByDesc('created_at');
            } else if($this->values->sort=='oldest') {
                $elements = $elements->sortBy('created_at');
            } else if($this->values->sort=='random') {
                $elements = $elements->random($this->values->limit);
            }
            $this->elements = $elements->take($this->values->limit)->values();
        }
    }

    public function baseFields()
    {
        return [
            Select::make('Show')->default($this->showDefault())->options($this->showOptions()),
            Select::make('Sort')->default($this->default_sort)->options(['latest' => __('Latest'), 'oldest' => __('Oldest'), 'random' => __('Random')]),
            Number::make('Limit of results', 'limit')->default($this->default_limit),
        ];
            
    }

    private function showOptions()
    {
        $options = collect([
            'faqs' => __('Faq'),
            'partners' => __('Partners'), 
            'promotions' => __('Promotions'), 
            'products' => __('Products'),
            'banners' => __('Banners'), 
            'jobs' => __('Jobs'),
            'multimedia' => __('Multimedia'),
            'music_albums' => __('Music Album'),
            'music_songs' => __('Music Song'),
            'news' => __('News'),
            'documentations' => __('Documentation'),
            'offices' => __('Offices'),
            'testimonials' => __('Testimonials'),
        ]);
        if($this->contents) {
            $options['contents'] = __('Content');
        }

        // Filter if use of include of exclude on class
        if(isset($this->include_options)) {
            $options = $options->filter(function($title, $key) {
                return in_array($key, $this->include_options);
            });
        }

        if(isset($this->exclude_options)) {
            $options = $options->filter(function($title, $key) {
                return !in_array($key, $this->exclude_options);
            });
        }
        return $options->sort()->all();
    }

    private function showDefault()
    {
        if(isset($this->default_result)) {
            return $this->default_result;
        }
        if($this->contents) {
            return 'contents';
        }
        return collect($this->showOptions())->keys()->first();
    }
}