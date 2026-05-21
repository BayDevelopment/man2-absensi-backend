<?php

namespace App\Services;

class FaceRecognitionService
{
    /**
     * Threshold umum untuk descriptor face-api.js.
     * Semakin kecil semakin ketat.
     */
    private float $threshold = 0.55;

    public function compare(?array $registeredDescriptor, ?array $incomingDescriptor): array
    {
        if (!$registeredDescriptor || !$incomingDescriptor) {
            return [
                'matched' => false,
                'distance' => null,
                'confidence' => null,
                'message' => 'Data wajah tidak lengkap.',
            ];
        }

        if (count($registeredDescriptor) !== count($incomingDescriptor)) {
            return [
                'matched' => false,
                'distance' => null,
                'confidence' => null,
                'message' => 'Format descriptor wajah tidak valid.',
            ];
        }

        $sum = 0;

        foreach ($registeredDescriptor as $i => $value) {
            $diff = (float) $value - (float) $incomingDescriptor[$i];
            $sum += $diff * $diff;
        }

        $distance = sqrt($sum);

        /**
         * Confidence versi sederhana.
         * Semakin kecil distance, semakin tinggi confidence.
         */
        $confidence = max(0, min(1, 1 - $distance));

        return [
            'matched' => $distance <= $this->threshold,
            'distance' => round($distance, 4),
            'confidence' => round($confidence, 4),
            'message' => $distance <= $this->threshold
                ? 'Data wajah ditemukan.'
                : 'Wajah tidak sama.',
        ];
    }
}
