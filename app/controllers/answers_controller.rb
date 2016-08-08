class AnswersController < ApplicationController
  def index
    answers = nil

    if params[:question_id]
      answers = Answer.all.where question_id: params[:question_id]
    else
      answers = current_user.person.answers
    end

    render json: answers
  end

  def create
    question = Question.find params[:question_id]
    person = current_user.person
    answer = Answer.new text: params[:text], anonymous: params[:anonymous] || false, created_at: DateTime.now,
                            question: question, person: person
    answer.save!
    render json: answer
  end

  def show
    render json: Answer.find(params[:id])
  end

  def update
    render plain: 'I update one entity'
  end

  def destroy
    render plain: 'I destroy one entity'
  end

  def upvote
    vote = Vote.find_or_create_by(person: current_user.person, votable: Answer.find(params[:id]))
    vote.up = true
    vote.save!

    render json: vote
  end

  def downvote
    vote = Vote.find_or_create_by(person: current_user.person, votable: Answer.find(params[:id]))
    vote.up = false
    vote.save!

    render json: vote
  end

  def vote
    Vote.find(person: current_user.person, votable: Answer.find(params[:id])).delete

    render status: 200, body: nil
  end

  def accept
    answer = Answer.find params[:id]
    question = answer.question

    if !question.answer
      answer.accepted = true
      answer.save!

      render status: 200, body: nil
    else
      render status: 401, body: nil
    end
  end

  def undo_accept
    answer = Answer.find params[:id]
    question = answer.question

    if question.answer == answer
      answer.accepted = false
      answer.save!

      render status: 200, body: nil
    else
      render status: 401, body: nil
    end
  end
end
