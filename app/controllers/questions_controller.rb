class QuestionsController < ApplicationController
  def index
    resp = current_person.questions

    resp = Presentation.find(params[:presentation_id]).questions if params[:presentation_id]

    render json: resp
  end

  def create
    presentation = Presentation.find params[:presentation_id]
    person = current_person
    question = Question.new text: params[:text], anonymous: params[:anonymous] || false, created_at: DateTime.now,
    presentation: presentation, person: person, cluster: nil, isfaq: false
    question.save!
    system "python clustering.py -idf 0.4 15 "+params[:presentation_id]
    render json: question
  end

  def show
    question = Question.find(params[:id])

    puts question.answer

    render json: question

  end

  def update
    question = Question.find(params[:id])
    
    question.update(isfaq: params[:isfaq])

    question.save

    render json: question
  end

  def destroy
    render plain: 'I destroy one entity'
  end

  def upvote
    vote = Vote.find_or_create_by(person: current_person, votable: Question.find(params[:id]))
    vote.up = true
    vote.save!

    render json: vote
  end

  def downvote
    vote = Vote.find_or_create_by(person: current_person, votable: Question.find(params[:id]))
    vote.up = false
    vote.save!

    render json: vote
  end

  def vote
    Vote.find_by(person: current_person, votable: Question.find(params[:id])).delete
      
    render status: 200, body: nil
  end
end
