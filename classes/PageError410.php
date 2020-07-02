<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle an error 410 page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageError410 extends \Frontend
{
	/**
	 * Generate an error 410 page
	 */
	public function generate()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj410 = $this->prepare();
		$objPage = $obj410->loadDetails();

		// Reset inherited cache timeouts (see #231)
		if (!$objPage->includeCache)
		{
			$objPage->cache = 0;
			$objPage->clientCache = 0;
		}

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		header('HTTP/1.1 410 Not Found');
		$objHandler->generate($objPage);
	}

	/**
	 * Return a response object
	 *
	 * @return Response
	 */
	public function getResponse()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj410 = $this->prepare();
		$objPage = $obj410->loadDetails();

		// Reset inherited cache timeouts (see #231)
		if (!$objPage->includeCache)
		{
			$objPage->cache = 0;
			$objPage->clientCache = 0;
		}

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		return $objHandler->getResponse($objPage)->setStatusCode(410);
	}

	/**
	 * Prepare the output
	 *
	 * @return PageModel
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function prepare()
	{
		// Find the matching root page
		$objRootPage = $this->getRootPageFromUrl();

		// Forward if the language should be but is not set (see #4028)
		if (Config::get('addLanguageToUrl'))
		{
			// Get the request string without the script name
			$strRequest = Environment::get('relativeRequest');

			// Only redirect if there is no language fragment (see #4669)
			if ($strRequest != '' && !preg_match('@^[a-z]{2}(-[A-Z]{2})?/@', $strRequest))
			{
				// Handle language fragments without trailing slash (see #7666)
				if (preg_match('@^[a-z]{2}(-[A-Z]{2})?$@', $strRequest))
				{
					$this->redirect(Environment::get('request') . '/', 301);
				}
				else
				{
					if ($strRequest == Environment::get('request'))
					{
						$strRequest = $objRootPage->language . '/' . $strRequest;
					}
					else
					{
						$strRequest = Environment::get('script') . '/' . $objRootPage->language . '/' . $strRequest;
					}

					$this->redirect($strRequest, 301);
				}
			}
		}

		// Look for a 410 page
		$obj410 = PageModelExt::find410ByPid($objRootPage->id);

		// Die if there is no page at all
		if (null === $obj410)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Forward to another page
		if ($obj410->autoforward && $obj410->jumpTo)
		{
			$objNextPage = PageModel::findPublishedById($obj410->jumpTo);

			if (null === $objNextPage)
			{
				$this->log('Forward page ID "' . $obj410->jumpTo . '" does not exist', __METHOD__, TL_ERROR);

				throw new ForwardPageNotFoundException('Forward page not found');
			}

			$this->redirect($objNextPage->getFrontendUrl(), (($obj410->redirect == 'temporary') ? 302 : 301));
		}

		return $obj410;
	}
}

class_alias(PageError410::class, 'PageError410');