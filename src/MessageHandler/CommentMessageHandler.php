<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private WorkflowInterface $workflow;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
        private MessageBusInterface $bus,
        private MailerInterface $mailer,
        private string $adminEmail,
        WorkflowInterface $commentStateMachine,
        private LoggerInterface|null $logger = null,
    ){
        $this->workflow = $commentStateMachine;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());

        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'accept';
            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }
            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();

            $this->bus->dispatch($message);
        } elseif ($this->workflow->can($comment, 'publish') || $this->workflow->can($comment, 'publish_ham')) {
            $this->mailer->send((new NotificationEmail())
                        ->subject('New comment posted')
                        ->htmlTemplate('emails/comment_notification.html.twig')
                        ->to($this->adminEmail)
                        ->context(['comment' => $comment])
            );

        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }
    }
}