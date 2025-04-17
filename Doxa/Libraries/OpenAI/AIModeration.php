<?php

namespace Doxa\Libraries\OpenAI;

use Doxa\Core\Helpers\Error;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIModeration
{
    protected string $api_key;
    protected string $api_url;
    protected string $content_type;
    protected string $model;
    protected string $input_text = '';
    protected string $image_url = '';
    protected array $mod_image_data = [];
    protected array $mod_text_data = [];
    private $request_data;
    private $response;

    public function __construct()
    {
        $this->api_key = config('services.open_ai.key');
        $this->api_url = 'https://api.openai.com/v1/moderations';
        $this->content_type = "application/json";
        $this->model = "omni-moderation-latest";
    }

    public function setInputText(string $text)
    {
        $this->input_text = $text;
    }

    public function setImage(string $image)
    {
        $this->image_url = $image;
    }

    private function sendRequest()
    {
        return Http::withHeaders([
            'Content-Type' => $this->content_type,
            'Authorization' => 'Bearer ' . $this->api_key,
        ])->withBody($this->request_data, $this->content_type)->post($this->api_url);
    }

    public function moderateText($text)
    {
        if($text){
            $this->input_text = $text;
        }

        $this->request_data = json_encode([
            'model' => $this->model,
            'input' => $this->input_text
        ]);

        $response = $this->sendRequest();

        if ($response->failed()) {
            //Log::error('Failed to moderate text: ' . $response->body());
            Clog::write('ai_moderation', 'Failed to moderate text: ' . $response->body());
            //Error::add('Failed to moderate text: ' . $response->body());
        }

        return $this->processResponse($response->json());

    }

    public function moderateImage($image_url)
    {
        $this->request_data = json_encode([
            'model' => $this->model,
            'input' => [
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $image_url
                    ],
                ]
            ]
        ]);


        $response = $this->sendRequest();

        if ($response->failed()) {
            //Log::error('Failed to moderate image: ' . $response->body());
            Clog::write('ai_moderation', 'Failed to moderate image: ' . $response->body());
            //Error::add('Failed to moderate image: ' . $response->body());
        }

        return $this->processResponse($response->json());
    }

    private function processResponse(array $response)
    {
        //dd($response['error']);
        if(!empty($response['error'])) {
            $response['error']['error'] = true;
            return $response['error'];
        }

        $result = $response['results'][0] ?? [];
        $flagged = $result['flagged'] ?? false;
        $categories = $result['categories'] ?? [];

        $flagged_for = array_keys(array_filter($categories));
        $flagged_message = $flagged ? 'This content has been flagged for the following reasons: ' . implode(', ', $flagged_for) : '';
        $category_scores = $result ? $result['category_scores'] : [];

        return [
            'flagged' => $flagged,
            'flagged_for' => $flagged_for,
            'flagged_message' => $flagged_message,
            'flag_scores' => $category_scores,
        ];
    }

    public function getResults()
    {
        return [
            'text_res' => $this->mod_text_data,
            'image_res' => $this->mod_image_data
        ];
    }

    public function isImageFlagged()
    {
        return $this->mod_image_data['flagged'];
    }

    public function isTextFlagged()
    {
        return $this->mod_text_data['flagged'];
    }
}
