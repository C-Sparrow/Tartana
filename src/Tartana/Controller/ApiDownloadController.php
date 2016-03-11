<?php
namespace Tartana\Controller;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Entity\Download;
use Tartana\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiDownloadController extends Controller
{

	/**
	 * @Route("/api/v1/download/find/{state}", name="api_v1_download_find",
	 * defaults={"state" = null})
	 */
	public function findAction ($state)
	{
		if ($state)
		{
			$state = explode(',', $state);
		}

		/** @var \Tartana\Domain\DownloadRepository $repository **/
		$repository = $this->container->get('DownloadRepository');
		$downloads = $repository->findDownloads($state);

		$t = $this->container->get('Translator');
		$data = [];
		$sizes = [
				$t->trans('TARTANA_TEXT_SIZE_BYTE'),
				$t->trans('TARTANA_TEXT_SIZE_KILO_BYTE'),
				$t->trans('TARTANA_TEXT_SIZE_MEGA_BYTE'),
				$t->trans('TARTANA_TEXT_SIZE_GIGA_BYTE'),
				$t->trans('TARTANA_TEXT_SIZE_TERRA_BYTE'),
				$t->trans('TARTANA_TEXT_SIZE_PETA_BYTE')
		];
		foreach ($downloads as $download)
		{
			$d = $download->toArray();
			$d['message'] = $t->trans($download->getMessage());
			$d['state'] = $t->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . $download->getState());
			$d['size'] = Util::readableSize($download->getSize(), $sizes);
			$data[] = $d;
		}

		$data = [
				'success' => true,
				'message' => '',
				'data' => $data
		];

		return new JsonResponse($data);
	}

	/**
	 * @Route("/api/v1/download/clearall", name="api_v1_download_clearall")
	 */
	public function clearallAction ()
	{
		return $this->handleCommand(new DeleteDownloads($this->container->get('DownloadRepository')
			->findDownloads()));
	}

	/**
	 * @Route("/api/v1/download/clearcompleted",
	 * name="api_v1_download_clearcompleted")
	 */
	public function clearcompletedAction ()
	{
		return $this->handleCommand(
				new DeleteDownloads($this->container->get('DownloadRepository')
					->findDownloads(Download::STATE_PROCESSING_COMPLETED)));
	}

	/**
	 * @Route("/api/v1/download/resumefailed",
	 * name="api_v1_download_resumefailed")
	 */
	public function resumefailedAction ()
	{
		return $this->handleCommand(
				new ChangeDownloadState($this->container->get('DownloadRepository'),
						[
								Download::STATE_DOWNLOADING_ERROR,
								Download::STATE_PROCESSING_ERROR
						], Download::STATE_DOWNLOADING_NOT_STARTED));
	}

	/**
	 * @Route("/api/v1/download/resumeall",
	 * name="api_v1_download_resumeall")
	 */
	public function resumeallAction ()
	{
		return $this->handleCommand(
				new ChangeDownloadState($this->container->get('DownloadRepository'),
						[
								Download::STATE_DOWNLOADING_STARTED,
								Download::STATE_DOWNLOADING_COMPLETED,
								Download::STATE_DOWNLOADING_ERROR,
								Download::STATE_PROCESSING_NOT_STARTED,
								Download::STATE_PROCESSING_STARTED,
								Download::STATE_PROCESSING_COMPLETED,
								Download::STATE_PROCESSING_ERROR
						], Download::STATE_DOWNLOADING_NOT_STARTED));
	}

	/**
	 * @Route("/api/v1/download/reprocess",
	 * name="api_v1_download_reprocess")
	 */
	public function reprocessAction ()
	{
		return $this->handleCommand(
				new ChangeDownloadState($this->container->get('DownloadRepository'),
						[
								Download::STATE_PROCESSING_NOT_STARTED,
								Download::STATE_PROCESSING_STARTED,
								Download::STATE_PROCESSING_COMPLETED,
								Download::STATE_PROCESSING_ERROR
						], Download::STATE_DOWNLOADING_COMPLETED));
	}

	private function handleCommand ($command)
	{
		$commandBus = $this->container->get('CommandBus');
		$commandBus->handle($command);

		$data = [
				'success' => true,
				'message' => ''
		];

		return new JsonResponse($data);
	}
}
