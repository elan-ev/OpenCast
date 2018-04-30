<?php

/**
 * Class xoctMedia
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctMedia extends xoctPublicationMetadata {

	/**
	 * @var bool
	 */
	public $has_audio;
	/**
	 * @var bool
	 */
	public $has_video;
	/**
	 * @var int
	 */
	public $duration;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var int
	 */
	public $width;
	/**
	 * @var int
	 */
	public $height;


	/**
	 * @return bool
	 */
	public function isHasAudio() {
		return $this->has_audio;
	}


	/**
	 * @param bool $has_audio
	 */
	public function setHasAudio($has_audio) {
		$this->has_audio = $has_audio;
	}


	/**
	 * @return bool
	 */
	public function isHasVideo() {
		return $this->has_video;
	}


	/**
	 * @param bool $has_video
	 */
	public function setHasVideo($has_video) {
		$this->has_video = $has_video;
	}


	/**
	 * @return int
	 */
	public function getDuration() {
		return $this->duration;
	}


	/**
	 * @param int $duration
	 */
	public function setDuration($duration) {
		$this->duration = $duration;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}


	/**
	 * @param int $width
	 */
	public function setWidth($width) {
		$this->width = $width;
	}


	/**
	 * @return int
	 */
	public function getHeight() {
		return $this->height;
	}


	/**
	 * @param int $height
	 */
	public function setHeight($height) {
		$this->height = $height;
	}
}