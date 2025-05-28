<?php

declare(strict_types=1);

namespace Endroid\QrCode;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Encoding\EncodingInterface;

final readonly class QrCode implements QrCodeInterface
{
    public function __construct(
        private string $data,
        private EncodingInterface $encoding = new Encoding('UTF-8'),
        private ErrorCorrectionLevel $errorCorrectionLevel = ErrorCorrectionLevel::Low,
        private int $size = 300,
        private int $margin = 10,
        private RoundBlockSizeMode $roundBlockSizeMode = RoundBlockSizeMode::Margin,
        private ColorInterface $foregroundColor = new Color(0, 0, 0),
        private ColorInterface $backgroundColor = new Color(255, 255, 255),
    ) {
    }

    // Static factory method to create an instance
    public static function create(string $data): self
    {
        return new self($data);
    }

    // Fluent setters returning a new instance with updated value
    public function withEncoding(EncodingInterface $encoding): self
    {
        return new self(
            $this->data,
            $encoding,
            $this->errorCorrectionLevel,
            $this->size,
            $this->margin,
            $this->roundBlockSizeMode,
            $this->foregroundColor,
            $this->backgroundColor
        );
    }

    public function withErrorCorrectionLevel(ErrorCorrectionLevel $errorCorrectionLevel): self
    {
        return new self(
            $this->data,
            $this->encoding,
            $errorCorrectionLevel,
            $this->size,
            $this->margin,
            $this->roundBlockSizeMode,
            $this->foregroundColor,
            $this->backgroundColor
        );
    }

    public function withSize(int $size): self
    {
        return new self(
            $this->data,
            $this->encoding,
            $this->errorCorrectionLevel,
            $size,
            $this->margin,
            $this->roundBlockSizeMode,
            $this->foregroundColor,
            $this->backgroundColor
        );
    }

    public function withMargin(int $margin): self
    {
        return new self(
            $this->data,
            $this->encoding,
            $this->errorCorrectionLevel,
            $this->size,
            $margin,
            $this->roundBlockSizeMode,
            $this->foregroundColor,
            $this->backgroundColor
        );
    }

    public function withRoundBlockSizeMode(RoundBlockSizeMode $roundBlockSizeMode): self
    {
        return new self(
            $this->data,
            $this->encoding,
            $this->errorCorrectionLevel,
            $this->size,
            $this->margin,
            $roundBlockSizeMode,
            $this->foregroundColor,
            $this->backgroundColor
        );
    }

    public function withForegroundColor(ColorInterface $foregroundColor): self
    {
        return new self(
            $this->data,
            $this->encoding,
            $this->errorCorrectionLevel,
            $this->size,
            $this->margin,
            $this->roundBlockSizeMode,
            $foregroundColor,
            $this->backgroundColor
        );
    }

    public function withBackgroundColor(ColorInterface $backgroundColor): self
    {
        return new self(
            $this->data,
            $this->encoding,
            $this->errorCorrectionLevel,
            $this->size,
            $this->margin,
            $this->roundBlockSizeMode,
            $this->foregroundColor,
            $backgroundColor
        );
    }

    // Getters (unchanged)
    public function getData(): string
    {
        return $this->data;
    }

    public function getEncoding(): EncodingInterface
    {
        return $this->encoding;
    }

    public function getErrorCorrectionLevel(): ErrorCorrectionLevel
    {
        return $this->errorCorrectionLevel;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getMargin(): int
    {
        return $this->margin;
    }

    public function getRoundBlockSizeMode(): RoundBlockSizeMode
    {
        return $this->roundBlockSizeMode;
    }

    public function getForegroundColor(): ColorInterface
    {
        return $this->foregroundColor;
    }

    public function getBackgroundColor(): ColorInterface
    {
        return $this->backgroundColor;
    }
}
