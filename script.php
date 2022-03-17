<?php

class Clips
{
    protected static ?Clips $instance = null;
    protected array $config = [];
    protected array $items = [];

    public static function instance(): ?Clips
    {
        if (self::$instance === null) {
            self::$instance = new Clips;
        }

        return self::$instance;
    }

    protected function line(string $string)
    {
        echo $string.PHP_EOL;
    }

    protected function __construct()
    {
        $this->line('Start');
        $this->config = require_once __DIR__.'/config.php';
        $this->getClips();
    }

    protected function getClips(?string $after = null)
    {
        $this->line('Make a Twitch API request');
        $curl = curl_init();

        if ($after) {
            sleep($this->config['sleep']);
        }

        $query = [
            'broadcaster_id' => $this->config['broadcaster_id'],
            'first' => 100,
        ];

        if ($after) {
            $query = array_merge($query, [
                'after' => $after,
            ]);
        }

        if ($this->config['started_at']) {
            $query = array_merge($query, [
                'started_at' => $this->config['started_at'],
            ]);
        }

        if ($this->config['ended_at']) {
            $query = array_merge($query, [
                'ended_at' => $this->config['ended_at'],
            ]);
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.twitch.tv/helix/clips?'.http_build_query($query),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'client-id: '.$this->config['client_id'],
                'Authorization: Bearer '.$this->config['token']
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);
        $items = $data['data'];
        foreach ($items as $item) {
            if ($item['game_id'] == $this->config['game_id']) {
                $this->items[] = [
                    'title'         => $item['title'],
                    'duration'      => $item['duration'],
                    'view_count'    => $item['view_count'],
                    'url'           => $item['url'],
                    'creator_name'  => $item['creator_name'],
                    'creator_id'    => $item['creator_id'],
                    'created_at'    => $item['created_at'],
                    'thumbnail_url' => $item['thumbnail_url'],
                ];
            }
        }

        if (!empty($data['pagination']['cursor'])) {
            $this->getClips($data['pagination']['cursor']);
            return;
        }

        $this->line('Write file');

        file_put_contents('data.json', json_encode($this->items));

        $this->line('Finished');
    }
}

Clips::instance();
