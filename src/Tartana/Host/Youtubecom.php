<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DomCrawler\Crawler;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;
use Tartana\Util;

class Youtubecom extends Http
{

	public function fetchLinkList ($link)
	{
		if (! Util::startsWith($link, 'https://www.youtube.com/playlist?'))
		{
			return parent::fetchLinkList($link);
		}

		$resp = $this->getClient()->request('get', $link);

		$crawler = new Crawler($resp->getBody()->getContents());

		// Extracting the links which belong to the playlist
		$links = $crawler->filter('a.pl-video-title-link')->extract([
				'href'
		]);

		$data = [];
		foreach ($links as $link)
		{
			$uri = parse_url($link);

			if (! key_exists('path', $uri) || $uri['path'] != '/watch')
			{
				continue;
			}

			$params = [];
			parse_str($uri['query'], $params);
			if (key_exists('v', $params))
			{
				$data[] = 'https://www.youtube.com/watch?v=' . $params['v'];
			}
		}
		return array_unique($data);
	}

	public function fetchDownloadInfo (array $downloads)
	{
		foreach ($downloads as $download)
		{
			// Connection check
			try
			{
				$data = $this->getStreamData($download);
				if (! empty($data['title']) && empty($download->getFileName()))
				{
					$download->setFileName($this->makeSafe($data['title'] . '.mp4'));
				}

				if (key_exists('errorcode', $data) && $data['errorcode'] > 0 && key_exists('reason', $data))
				{
					$download->setMessage($data['reason']);
					$download->setState(Download::STATE_DOWNLOADING_ERROR);
				}
			}
			catch (\Exception $e)
			{
				$this->log('Exception fetching head for connection test: ' . $e->getMessage());
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_URL');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
			}
		}
	}

	protected function getUrlToDownload (Download $download)
	{
		$data = $this->getStreamData($download);

		if (! key_exists('url_encoded_fmt_stream_map', $data))
		{
			return null;
		}

		foreach (explode(',', $data['url_encoded_fmt_stream_map']) as $streamData)
		{
			parse_str($streamData, $streamData);

			if (key_exists('url', $streamData))
			{
				$url = urldecode($streamData['url']);
				$this->log('Youtube real url: ' . $url);
				return $url;
			}
		}
		return null;
	}

	private function getStreamData (Download $download)
	{
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $download->getLink(),
				$match))
		{
			$headers = [
					'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:10.0) Gecko/20150101 Firefox/44.0 (Chrome)',
					'Referer' => $download->getLink(),
					'Accept-Language' => 'en-us,en;q=0.5',
					'Accept-Encoding' => 'gzip, deflate',
					'Accept-Charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
					'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'Connection' => 'close',
					'Origin' => 'https://www.youtube.com',
					'Host' => 'www.youtube.com'
			];
			// Getting the link information initializing the cookie
			$this->getClient()->request('get', $download->getLink() . '&gl=US&hl=en&has_verified=1&bpctr=9999999999',
					[
							RequestOptions::HEADERS => $headers
					]);

			// Fetching the video information
			$res = $this->getClient()->request('get',
					'http://www.youtube.com/get_video_info?video_id=' . $match[1] . '&el=info&ps=default&eurl=&gl=US&hl=en',
					[
							RequestOptions::HEADERS => $headers
					]);

			parse_str($res->getBody()->getContents(), $videoData);
			return $videoData;
		}

		return [
				'title' => '',
				'url_encoded_fmt_stream_map' => ''
		];
	}

	/**
	 *
	 * @param string $file
	 * @return string
	 * @see http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
	 * @see http://stackoverflow.com/a/24812812/356375
	 */
	private function makeSafe ($file)
	{
		// Remove any trailing dots, as those aren't ever valid file names.
		$file = rtrim($file, '.');

		$regex = array(
				'#(\.){2,}#',
				'#[^A-Za-z0-9\.\_\- ]#',
				'#^\.#'
		);

		return trim(preg_replace($regex, '', $file));
	}
}
