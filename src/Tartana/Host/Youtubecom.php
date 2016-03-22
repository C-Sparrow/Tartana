<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Request;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;

class Youtubecom extends Http
{

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

				if (key_exists('errorcode', $data) && $data['errorcode'] > 0&&key_exists('reason', $data))
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
			// Getting the link information
			$res = $this->getClient()->request('get', 'http://www.youtube.com/get_video_info?video_id=' . $match[1]);

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
