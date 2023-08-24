<?php

namespace srag\Plugins\Opencast\Model\Series;

use srag\Plugins\Opencast\Model\Series\Request\CreateSeriesRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesACLRequest;
use srag\Plugins\Opencast\Model\Series\Request\UpdateSeriesMetadataRequest;
use srag\Plugins\Opencast\Model\User\xoctUser;
use xoctException;

/**
 * Class SeriesRepository
 *
 * @package srag\Plugins\Opencast\Model\API\Series
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
interface SeriesRepository
{
    public function find(string $identifier): Series;

    public function fetch(string $identifier): Series;

    /**
     * @return ?string series identifier
     */
    public function create(CreateSeriesRequest $request): ?string;

    public function updateMetadata(UpdateSeriesMetadataRequest $request): void;

    public function updateACL(UpdateSeriesACLRequest $request): void;

    /**
     * @return Series[]
     */
    public function getAllForUser(string $user_string): array;

    /**
     * @throws xoctException
     */
    public function getOrCreateOwnSeries(xoctUser $xoct_user): Series;

    /**
     * @return Series|null
     * @throws xoctException
     */
    public function getOwnSeries(xoctUser $xoct_user);

    public function getOwnSeriesTitle(xoctUser $xoct_user): string;
}
