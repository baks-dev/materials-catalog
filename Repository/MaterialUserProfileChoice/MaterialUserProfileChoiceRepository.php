<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Materials\Catalog\Repository\MaterialUserProfileChoice;

use BaksDev\Auth\Email\Entity\Account;
use BaksDev\Auth\Email\Entity\Status\AccountStatus;
use BaksDev\Auth\Email\Type\EmailStatus\EmailStatus;
use BaksDev\Auth\Email\Type\EmailStatus\Status\EmailStatusActive;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use Generator;

final class MaterialUserProfileChoiceRepository implements MaterialUserProfileChoiceInterface
{
    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Возвращает список профилей пользователей, доступных к созданию карточек
     */
    public function getProfileCollection(): Generator
    {

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(UserProfile::class, 'user_profile');

        $dbal->join(
            'user_profile',
            UserProfileInfo::class,
            'info',
            'info.profile = user_profile.id AND info.status = :status',
        )
            ->setParameter(
                'status',
                UserProfileStatusActive::class,
                UserProfileStatus::TYPE
            );


        $dbal->join(
            'user_profile',
            UserProfilePersonal::class,
            'personal',
            'personal.event = user_profile.event',
        );

        $dbal->join(
            'info',
            Account::class,
            'account',
            'account.id = info.usr',
        );

        $dbal->join(
            'account',
            AccountStatus::class,
            'status',
            'status.event = account.event AND status.status = :account_status',
        )
            ->setParameter(
                'account_status',
                new EmailStatus(EmailStatusActive::class),
                EmailStatus::TYPE
            );


        /** Свойства конструктора объекта гидрации */

        $dbal->addSelect('user_profile.id AS value');
        $dbal->addSelect('personal.username AS attr');

        return $dbal->fetchAllHydrate(UserProfileUid::class);

    }

}
