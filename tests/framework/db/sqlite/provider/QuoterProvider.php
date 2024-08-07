<?php

declare(strict_types=1);

namespace yiiunit\framework\db\sqlite\provider;

use yiiunit\framework\db\provider\AbstractQuoterProvider;

final class QuoterProvider extends AbstractQuoterProvider
{
   /**
     * @return string[][]
     */
    public static function stringValues(): array
    {
        return [
            ['string', "'string'"],
            ["It's interesting", "'It''s interesting'"],
        ];
    }
}
