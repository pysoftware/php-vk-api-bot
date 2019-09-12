<?php
// KEYS
const VK_API_TOKEN = 'YOUR_API_VK_TOKEN';
const VK_API_CONFIRM = 'KEY_TO_CONFIRM';
const MOVIES_API_KEY = 'YOUR_DB_MOVIES_API_KEY';

// CONST
const SINGLE_MOVIE = 's_movie';
const POPULAR_MOVIES = 'p_movie';
const GENRES = 'genres';
const GENRE_ACTION = 28;
const GENRE_ADVENTURE = 12;
const GENRE_ANIMATION = 16;

// URLS
const POPULAR_MOVIES_URL = 'https://api.themoviedb.org/3/movie/popular?api_key=' . MOVIES_API_KEY . '&language=ru-RU&page=1&region=RU';
const SINGLE_MOVIE_URL = 'https://api.themoviedb.org/3/movie/76341?api_key=' . MOVIES_API_KEY;
const GENRES_URL = 'https://api.themoviedb.org/3/genre/movie/list?api_key=' . MOVIES_API_KEY . '&language=ru-RU';
const MOVIES_POSTER_URL = 'https://image.tmdb.org/t/p/w500';
const SEARCH_FOR_GENRE_URL = 'https://api.themoviedb.org/3/discover/movie?api_key=' . MOVIES_API_KEY . 'efc0&language=ru-RU&region=RU&sort_by=popularity.desc&include_adult=false&include_video=false&with_genres=';
const SEARCH_FOR_SINGLE_MOVIE_URL = 'https://api.themoviedb.org/3/discover/movie?api_key=8493300e4fdc02b9ad360b1ebb10efc0&language=ru-RU&region=RU&sort_by=popularity.desc&include_adult=false&include_video=false';

// GET RANDOM MOVIE
$singleMovieResponse = file_get_contents(SEARCH_FOR_SINGLE_MOVIE_URL . '&page=' . rand(1, 50));

$singleMovieID = rand(0, 19);
$singleMovieResponseData = json_decode($singleMovieResponse, true);

$singleMovieResponseData = json_decode($singleMovieResponse, true);

// GET SINGLE MOVIE DATA
$singleMovieTitle = $singleMovieResponseData['results'][$singleMovieID]['title'] ?? '';
$singleMoviePoster = $singleMovieResponseData['results'][$singleMovieID]['poster_path'] ?? '';
$singleMovieVoteAverage = $singleMovieResponseData['results'][$singleMovieID]['vote_average'] ?? '';
$singleMovieOverview = $singleMovieResponseData['results'][$singleMovieID]['overview'] ?? '';
$singleMovieReleaseDate = $singleMovieResponseData['results'][$singleMovieID]['release_date'] ?? '';
$singleMovieGenre = $singleMovieResponseData['results'][$singleMovieID]['genre_ids'] ?? '';

if (!isset($_REQUEST)) {
    return;
}

$data = json_decode(file_get_contents('php://input'));

// SEND REQUEST TO VK API
function request($message, $peer_id, $keyboard, $sticker_id)
{
    $request_params = array(
        'message' => $message,
        'peer_id' => $peer_id,
        'access_token' => VK_API_TOKEN,
        'v' => '5.80',
        'sticker_id' => $sticker_id,
        'keyboard' => json_encode($keyboard)
    );

    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
    echo('ok');
}

function getMovieByGenre($payload)
{
    $movie_id = rand(0, 19);
    $response = file_get_contents(SEARCH_FOR_GENRE_URL . $payload . '&page=' . rand(1, 50));
    $data = json_decode($response, true);
    $movieTitle = $data['results'][$movie_id]['title'] ?? '';
    $movieOverview = $data['results'][$movie_id]['overview'] ?? '';
    $movieVoteAverage = $data['results'][$movie_id]['vote_average'] ?? '';
    $movieReleaseDate = $data['results'][$movie_id]['release_date'] ?? '';
    if ($movieOverview !== '')
        return $movieTitle . '<br>' .
            'Оценка: ' . $movieVoteAverage . '<br>' .
            'Дата выхода: ' . $movieReleaseDate . '<br>' .
            'Описание: ' . $movieOverview;
    else
        return $movieTitle . '<br>' .
            'Оценка: ' . $movieVoteAverage . '<br>' .
            'Дата выхода: ' . $movieReleaseDate . '<br>';
}

//function getSingleMovie($title, $poster, $vote_average, $overview, $releaseDate, $peer_id, $keyboard)
function getSingleMovie($title, $vote_average, $overview, $releaseDate, $genres, $peer_id, $keyboard)
{
    $movieGenres = '';

    foreach ($genres as $genre) {
        $movieGenres .= localizeGenreID($genre) . ', ';
    }
    $movieGenres = rtrim($movieGenres, ', ');
    if ($overview !== '') {
        $message = $title . '<br>' .
            'Жанры: ' . $movieGenres . '<br>' .
            'Оценка: ' . $vote_average . '<br>' .
            'Дата выхода: ' . $releaseDate . '<br>' .
            'Описание: ' . $overview;
    } else {
        $message = $title . '<br>' .
            'Жанры: ' . $movieGenres . '<br>' .
            'Оценка: ' . $vote_average . '<br>' .
            'Дата выхода: ' . $releaseDate . '<br>';
    }

    request($message, $peer_id, $keyboard, '');
}

function getGenres($peer_id, $keyboard)
{
    request('Выбирай жанр!', $peer_id, $keyboard, '');
}

function getRandomMovieByGenre($peer_id, $keyboard, $payload)
{
    request(getMovieByGenre($payload), $peer_id, $keyboard, '');
}

function getPopularMovies($peed_id, $keyboard)
{
    $message = '';
    $counter = 0;
    $response = file_get_contents(POPULAR_MOVIES_URL);
    $data = json_decode($response, true);
    foreach (array_slice($data['results'], 0, 10) as $movie) {
        $counter++;
        $message .= $counter . '. ' . $movie['title'] . '<br>' .
            'Оценка: ' . $movie['vote_average'] . '<br>' .
            'Дата выхода: ' . $movie['release_date'] . '<br> <br>';
    }
    request($message, $peed_id, $keyboard, '');
}

// CHECK THE TYPE OF EVENTS VK API
switch ($data->type) {
    case 'confirmation':
        echo VK_API_CONFIRM;
        break;
    case 'message_new':
        $sticker_id = '14984';
        $message = 'Привет, давай я подскажу тебе фильм!';
        $peer_id = $data->object->peer_id;
        $body = $data->object->body;
        $payload = $data->object->payload;
//        $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token=" . VK_API_TOKEN . "&v=5.101"));

        if ($payload)
            $payload = json_decode($payload, true);

        // KEYBOARDS
        $defaultKeyboard = [
            'one_time' => true,
            'buttons' => [
                [
                    [
                        'action' =>
                            [
                                'type' => 'text',
                                'payload' => json_encode(SINGLE_MOVIE, JSON_UNESCAPED_UNICODE),
                                'label' => 'Посоветуй фильм',
                            ],
                        'color' => 'positive',
                    ],
                ],
                [
                    [
                        'action' =>
                            [
                                'type' => 'text',
                                'payload' => json_encode(POPULAR_MOVIES, JSON_UNESCAPED_UNICODE),
                                'label' => 'Популярные фильмы',
                            ],
                        'color' => 'primary',
                    ],
                ],
                [
                    [
                        'action' =>
                            [
                                'type' => 'text',
                                'payload' => json_encode(GENRES, JSON_UNESCAPED_UNICODE),
                                'label' => 'Жанры',
                            ],
                        'color' => 'primary',
                    ]
                ]
            ],
        ];
        $genresKeyboard = [
            'one_time' => true,
            'buttons' => [
                [
                    [
                        'action' =>
                            [
                                'type' => 'text',
                                'payload' => json_encode(GENRE_ACTION, JSON_UNESCAPED_UNICODE),
                                'label' => 'Хочу экшона!',
                            ],
                        'color' => 'positive',
                    ],
                ],
                [
                    [
                        'action' =>
                            [
                                'type' => 'text',
                                'payload' => json_encode(GENRE_ADVENTURE, JSON_UNESCAPED_UNICODE),
                                'label' => 'Хочу в путешествие!',
                            ],
                        'color' => 'primary',
                    ],
                ],
                [
                    [
                        'action' =>
                            [
                                'type' => 'text',
                                'payload' => json_encode(GENRE_ANIMATION, JSON_UNESCAPED_UNICODE),
                                'label' => 'Вернуться в детство!',
                            ],
                        'color' => 'primary',
                    ]
                ]
            ],
        ];

        // CHECK THE BUTTON PRESSED
        switch ($payload) {
            case SINGLE_MOVIE:
                getSingleMovie($singleMovieTitle, $singleMovieVoteAverage, $singleMovieOverview, $singleMovieReleaseDate, $singleMovieGenre, $peer_id, $defaultKeyboard);
                break;
            case GENRES:
                getGenres($peer_id, $genresKeyboard);
                break;
            case POPULAR_MOVIES:
                getPopularMovies($peer_id, $defaultKeyboard);
                break;
            case GENRE_ACTION:
            case GENRE_ANIMATION:
            case GENRE_ADVENTURE:
                getRandomMovieByGenre($peer_id, $defaultKeyboard, $payload);
                break;
        // NO BUTTONS WAS PRESSED
            default:
                request($message, $peer_id, $defaultKeyboard, $sticker_id);
                break;
        }
        break;
    case "message_reply":
        break;
}
