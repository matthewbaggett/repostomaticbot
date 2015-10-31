<?php
namespace Repostomatic;

use Coduo\PHPHumanizer\Number;
use Coduo\PHPHumanizer\DateTime;

use Repostomatic\Models\Message;
use Telegram\Bot\Api;
use Repostomatic\Models\Photo;

class RepostChecker
{

    /** @var Api */
    private $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function process(Message $message)
    {
        if ($message->photo_id > 0) {

            echo "Checking for duplicates.\n";
            echo "  (In {$message->getChat()->title}, is enabled? {$message->getChat()->report_reposts})\n";
            if ($message->getChat()->report_reposts == 'Yes') {
                $photo = $message->getPhoto();
                $duplicates = $photo->findDuplicates();
                echo "  There are " . count($duplicates) . " duplicates of {$photo->md5_sum}.\n";
                \Kint::dump($duplicates);
                $duplicatesCount = count($duplicates) - 1;
                if($duplicatesCount > 0) {
                    /** @var Photo $newestDuplicate */
                    $newestDuplicate = end($duplicates);
                    /** @var Photo $oldestDuplicate */
                    $oldestDuplicate = reset($duplicates);
                    \Kint::dump("Duplicate Finder!", $photo, $duplicates);
                    $messagesWithThisImage = Message::search()
                        ->where('photo_id', $newestDuplicate->photo_id)
                        ->where('telegram_message_id', $message->telegram_message_id, '!=')
                        ->exec();
                    $this->telegram->sendMessage(
                        $message->getChat()->telegram_id,
                        sprintf(
                            "This image has been seen %s before! First time I saw it was posted by %s, about %s",
                            ((count($messagesWithThisImage)>1) ? count($messagesWithThisImage) . " times": "1 time"),
                            $oldestDuplicate->getMessage()->getFrom()->getName(),
                            DateTime::difference(new \DateTime(), new \DateTime($newestDuplicate->getMessage()->date))
                        ),
                        false,
                        $message->telegram_message_id
                    );
                    echo "  FOUND!\n";
                    return;
                }
            }
            echo "  None.\n";
        }
        return;
    }
}
