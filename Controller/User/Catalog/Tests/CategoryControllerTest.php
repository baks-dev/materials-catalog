<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Catalog\Controller\User\Catalog\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Products\Category\Entity\CategoryProduct;
use BaksDev\Products\Category\Entity\Info\CategoryProductInfo;
use BaksDev\Users\User\Tests\TestUserAccount;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/** @group materials-catalog */
#[When(env: 'test')]
final class CategoryControllerTest extends WebTestCase
{
    private const string URL = '/catalog/%s';

    private static ?string $identifier = null;

    public static function setUpBeforeClass(): void
    {
        /** @var DBALQueryBuilder $qb */
        $qb = self::getContainer()->get(DBALQueryBuilder::class);
        $dbal = $qb->createQueryBuilder(self::class);

        $dbal->select('info.url');
        $dbal->from(CategoryProduct::class, 'category');
        $dbal->leftJoin(
            'category',
            CategoryProductInfo::class,
            'info',
            'info.event = category.event AND info.active = true'
        );

        $dbal->orderBy('category.event', 'DESC');

        self::$identifier = $dbal->fetchOne();
    }

    public function testSuccessful(): void
    {
        if(self::$identifier)
        {
            self::ensureKernelShutdown();
            $client = self::createClient();

            foreach(TestUserAccount::getDevice() as $device)
            {
                $client->setServerParameter('HTTP_USER_AGENT', $device);
                $client->request('GET', sprintf(self::URL, self::$identifier));
                self::assertResponseIsSuccessful();
            }
        }

        self::assertTrue(true);
    }
}
